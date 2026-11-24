<?php

namespace App\Http\Controllers\Api\Social;

use App\Jobs\Social\SyncSocialConversations;
use App\Jobs\Social\SyncSocialInsights;
use App\Jobs\Social\SyncSocialPosts;
use App\Models\Social\SocialAccount;
use App\Models\Social\SocialPage;
use App\Services\Social\Meta\FacebookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class SocialAccountController extends BaseSocialController
{
    protected FacebookService $facebookService;

    public function __construct(FacebookService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    /**
     * List all social accounts and their pages for the current tenant.
     * GET /api/v1/social/accounts
     */
    public function index(Request $request)
    {
        $accounts = $this->scopeTenant(SocialAccount::query(), $request)
            ->with(['pages' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($accounts, 'Social accounts retrieved successfully.');
    }

    /**
     * Disconnect a social account.
     * DELETE /api/v1/social/accounts/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $account = $this->scopeTenant(SocialAccount::query(), $request)->findOrFail($id);

        // Deactivate connected pages
        $account->pages()->update([
            'is_active' => false,
        ]);

        $account->update([
            'status' => 'disconnected',
        ]);

        return $this->success(null, 'Social account disconnected successfully.');
    }

    /**
     * Trigger manual background synchronization for an account.
     * POST /api/v1/social/accounts/{id}/sync
     */
    public function sync(Request $request, int $id)
    {
        $account = $this->scopeTenant(SocialAccount::query(), $request)
            ->with('pages')
            ->findOrFail($id);

        if ($account->status !== 'connected') {
            return $this->error('Cannot sync an expired or disconnected account. Please re-authorize.', 400);
        }

        $companyId = $account->company_id;
        $userId = $account->user_id;

        // Re-discover all managed pages and linked Instagram accounts from Meta
        try {
            $pages = $this->facebookService->getUserPages($account->access_token);

            foreach ($pages as $p) {
                $pageExternalId = (string) $p['id'];
                $pageName = $p['name'] ?? 'Facebook Page';
                $pageUsername = $p['username'] ?? null;
                $category = $p['category'] ?? null;
                $pageAccessToken = $p['access_token'] ?? $account->access_token;
                $igAccountData = $p['instagram_business_account'] ?? null;
                $igBusinessId = $igAccountData['id'] ?? null;

                // Sync Facebook Page record
                SocialPage::updateOrCreate(
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

                try {
                    $this->facebookService->subscribePageWebhooks($pageExternalId, $pageAccessToken);
                } catch (Throwable $we) {
                    Log::warning("Could not auto-subscribe page #{$pageExternalId}: {$we->getMessage()}");
                }

                // If linked Instagram business account exists, sync Instagram SocialPage
                if ($igBusinessId) {
                    SocialPage::updateOrCreate(
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
                            'access_token' => $pageAccessToken,
                            'is_active' => true,
                            'last_synced_at' => Carbon::now(),
                        ]
                    );
                }
            }
        } catch (Throwable $e) {
            Log::warning("Could not refresh pages during account sync #{$id}: {$e->getMessage()}");
        }

        // Fetch all active pages for dispatching sync jobs
        $activePages = SocialPage::where('social_account_id', $account->id)
            ->where('is_active', true)
            ->get();

        foreach ($activePages as $page) {
            try {
                SyncSocialPosts::dispatch($page->id);
                SyncSocialConversations::dispatch($page->id);
                SyncSocialInsights::dispatch($page->id);
            } catch (Throwable $se) {
                Log::warning("Sync job dispatch error for page #{$page->id}: {$se->getMessage()}");
            }
        }

        $account->update(['last_synced_at' => Carbon::now()]);

        return $this->success([
            'pages_count' => $activePages->count(),
        ], 'Synchronization completed for all connected pages.');
    }
}

