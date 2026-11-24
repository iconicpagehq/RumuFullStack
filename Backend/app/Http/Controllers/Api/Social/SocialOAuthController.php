<?php

namespace App\Http\Controllers\Api\Social;

use App\Jobs\Social\SyncSocialPosts;
use App\Models\Social\SocialAccount;
use App\Models\Social\SocialPage;
use App\Services\Social\Meta\FacebookService;
use App\Services\Social\Meta\InstagramService;
use App\Services\Social\Meta\MetaOAuthService;
use App\Services\Social\Meta\MetaTokenService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SocialOAuthController extends BaseSocialController
{
    protected MetaOAuthService $oauthService;
    protected MetaTokenService $tokenService;
    protected FacebookService $facebookService;
    protected InstagramService $instagramService;

    public function __construct(
        MetaOAuthService $oauthService,
        MetaTokenService $tokenService,
        FacebookService $facebookService,
        InstagramService $instagramService
    ) {
        $this->oauthService = $oauthService;
        $this->tokenService = $tokenService;
        $this->facebookService = $facebookService;
        $this->instagramService = $instagramService;
    }

    /**
     * Generate Meta OAuth connection URL.
     * GET /api/v1/social/connect/meta
     */
    public function connect(Request $request)
    {
        $tenant = $this->getTenantContext($request);
        $redirectUri = $request->query('redirect_uri');

        try {
            $url = $this->oauthService->generateAuthorizationUrl(
                $tenant['user_id'],
                $tenant['company_id'],
                $redirectUri
            );

            return $this->success([
                'authorization_url' => $url,
            ], 'Authorization URL generated successfully.');
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Handle Meta OAuth callback.
     * GET /api/v1/social/connect/meta/callback
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error_message') ?? $request->query('error_description');

        if ($error) {
            return $this->error("Meta authorization failed: {$error}", 400);
        }

        if (!$code || !$state) {
            return $this->error('Missing authorization code or state.', 400);
        }

        try {
            // Validate single-use state
            $oauthState = $this->oauthService->validateState($state);
            $companyId = $oauthState->company_id;
            $userId = $oauthState->user_id;

            // Exchange code for long-lived user token
            $tokenData = $this->oauthService->exchangeCodeForToken($code, $oauthState->redirect_uri);
            $userAccessToken = $tokenData['access_token'];
            $expiresAt = $tokenData['expires_at'];

            // Inspect token details & granted permissions
            $tokenInfo = $this->tokenService->inspectToken($userAccessToken);
            $grantedScopes = $tokenInfo['scopes'] ?? [];
            $metaUserId = $tokenInfo['user_id'] ?? null;

            // Extract target IDs from granular_scopes (for business-managed pages like Iconicpageshq)
            $targetIds = [];
            foreach ($tokenInfo['granular_scopes'] ?? [] as $gs) {
                foreach ($gs['target_ids'] ?? [] as $tid) {
                    $targetIds[$tid] = true;
                }
            }
            $targetIds = array_keys($targetIds);

            // Fetch user profile from Meta
            $profile = $this->facebookService->getUserProfile($userAccessToken);
            $metaUserId = $metaUserId ?: ($profile['id'] ?? null);
            $name = $profile['name'] ?? 'Meta User';
            $pictureUrl = $profile['picture']['data']['url'] ?? null;

            DB::beginTransaction();

            // 1. Create or update SocialAccount
            $account = SocialAccount::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'external_id' => (string) $metaUserId,
                    'platform' => 'facebook',
                ],
                [
                    'name' => $name,
                    'profile_picture_url' => $pictureUrl,
                    'access_token' => $userAccessToken,
                    'token_expires_at' => $expiresAt,
                    'permissions' => $grantedScopes,
                    'status' => 'connected',
                    'last_synced_at' => Carbon::now(),
                ]
            );

            // 2. Fetch all managed Facebook Pages and linked Instagram accounts
            $pages = $this->facebookService->getUserPages($userAccessToken, $targetIds);
            $syncedPages = [];

            foreach ($pages as $p) {
                $pageExternalId = (string) $p['id'];
                $pageName = $p['name'] ?? 'Facebook Page';
                $pageUsername = $p['username'] ?? null;
                $category = $p['category'] ?? null;
                $pageAccessToken = $p['access_token'] ?? $userAccessToken;
                $igAccountData = $p['instagram_business_account'] ?? null;
                $igBusinessId = $igAccountData['id'] ?? null;

                // Sync Facebook Page record
                $fbPage = SocialPage::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'user_id' => $userId,
                        'social_account_id' => $account->id,
                        'platform' => 'facebook',
                        'external_id' => $pageExternalId,
                    ],
                    [
                        'name' => $pageName,
                        'username' => $pageUsername,
                        'category' => $category,
                        'instagram_business_account_id' => $igBusinessId,
                        'access_token' => $pageAccessToken,
                        'is_active' => true,
                        'last_synced_at' => Carbon::now(),
                    ]
                );
                $syncedPages[] = $fbPage;

                // Try subscribing Webhooks for the page
                try {
                    $this->facebookService->subscribePageWebhooks($pageExternalId, $pageAccessToken);
                } catch (Throwable $we) {
                    Log::warning("Could not auto-subscribe page #{$pageExternalId} to webhooks: {$we->getMessage()}");
                }

                // If linked Instagram business account exists, create/update Instagram SocialPage
                if ($igBusinessId) {
                    $igPage = SocialPage::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'user_id' => $userId,
                            'social_account_id' => $account->id,
                            'platform' => 'instagram',
                            'external_id' => (string) $igBusinessId,
                        ],
                        [
                            'name' => $igAccountData['name'] ?? $igAccountData['username'] ?? "IG ({$pageName})",
                            'username' => $igAccountData['username'] ?? null,
                            'category' => 'Instagram Business',
                            'instagram_business_account_id' => (string) $igBusinessId,
                            'access_token' => $pageAccessToken, // Page token is used for IG Graph API
                            'is_active' => true,
                            'last_synced_at' => Carbon::now(),
                        ]
                    );
                    $syncedPages[] = $igPage;
                }
            }

            DB::commit();

            // Dispatch background initial post & conversation sync for all synced pages (safely isolated)
            foreach ($syncedPages as $sp) {
                try {
                    SyncSocialPosts::dispatch($sp->id);
                    \App\Jobs\Social\SyncSocialConversations::dispatch($sp->id);
                } catch (Throwable $se) {
                    Log::warning("Initial sync deferred for page #{$sp->id}: {$se->getMessage()}");
                }
            }

            return $this->success([
                'account' => $account->only(['id', 'name', 'platform', 'status', 'token_expires_at']),
                'pages_count' => count($syncedPages),
            ], 'Meta accounts and pages connected successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Social OAuth callback failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error("Connection failed: {$e->getMessage()}", 500);
        }
    }

    /**
     * Meta Data Deletion Request Callback (GDPR / Meta Platform Compliance).
     * POST /api/v1/social/meta/data-deletion
     */
    public function dataDeletionCallback(Request $request)
    {
        $signedRequest = $request->input('signed_request');
        $confirmationCode = 'del_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 16);

        if ($signedRequest) {
            try {
                $appSecret = config('services.meta.app_secret') ?: config('services.facebook.client_secret');
                if ($appSecret && str_contains($signedRequest, '.')) {
                    [$encodedSig, $payload] = explode('.', $signedRequest, 2);
                    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

                    if ($data && isset($data['user_id'])) {
                        $metaUserId = (string) $data['user_id'];
                        SocialAccount::where('external_id', $metaUserId)
                            ->update([
                                'status' => 'disconnected',
                                'access_token' => '',
                            ]);
                    }
                }
            } catch (Throwable $e) {
                Log::warning("Meta data deletion signed_request parse failed: {$e->getMessage()}");
            }
        }

        $statusUrl = url('/data-deletion?id=' . $confirmationCode);

        return response()->json([
            'url' => $statusUrl,
            'confirmation_code' => $confirmationCode,
        ]);
    }
}

