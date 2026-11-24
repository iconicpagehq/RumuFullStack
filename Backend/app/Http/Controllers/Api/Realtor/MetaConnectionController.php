<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Models\MetaPage;
use App\Models\MetaSyncLog;
use App\Services\MetaLeadApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MetaConnectionController extends ApiController
{
    private const SCOPES = [
        'pages_show_list',
        'pages_manage_metadata',
        'leads_retrieval',
    ];

    public function connectUrl(Request $request)
    {
        if (!config('services.meta.app_id')) {
            return $this->error('Meta integration is not configured on this server.', 503);
        }

        $redirectUri = $this->resolveRedirectUri($request->query('redirect_uri'));
        $state = Str::random(40);

        Cache::put(
            $this->stateKey($request->user()->id, $state),
            ['redirect_uri' => $redirectUri],
            now()->addMinutes(15)
        );

        $params = [
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'auth_type' => 'rerequest',
            'state' => $state,
        ];

        if (config('services.meta.config_id')) {
            $params['config_id'] = config('services.meta.config_id');
        } else {
            $params['scope'] = implode(',', self::SCOPES);
        }

        $graphVersion = config('services.meta.graph_version', 'v19.0');

        return $this->success([
            'url' => 'https://www.facebook.com/' . $graphVersion . '/dialog/oauth?' . http_build_query($params),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    public function connect(Request $request, MetaLeadApiService $metaApi)
    {
        $validated = $request->validate([
            'code' => 'nullable|string',
            'access_token' => 'nullable|string',
            'redirect_uri' => 'nullable|string|max:2048',
            'state' => 'nullable|string|max:100',
        ]);

        if (empty($validated['code']) && empty($validated['access_token'])) {
            return $this->error('An authorization code or access token is required.', 422);
        }

        $redirectUri = $this->consumeState($request, $validated) ?? $this->resolveRedirectUri($validated['redirect_uri'] ?? null);

        try {
            $userAccessToken = $validated['access_token'] ?? $this->exchangeCode($validated['code'], $redirectUri);

            $user = $request->user();

            $profileResponse = Http::timeout(15)->get($this->graphUrl('/me'), [
                'access_token' => $userAccessToken,
                'fields' => 'id,name,picture',
            ]);

            if ($profileResponse->successful()) {
                $profile = $profileResponse->json();
                $user->facebook_id = $profile['id'] ?? $user->facebook_id;
                if (empty($user->facebook_avatar) && !empty($profile['picture']['data']['url'])) {
                    $user->facebook_avatar = $profile['picture']['data']['url'];
                }
                $user->save();
            }

            $metaApi->syncUserPagesFromToken($user, $userAccessToken);

            MetaSyncLog::create([
                'user_id' => $user->id,
                'campaign_id' => null,
                'type' => 'success',
                'event_type' => 'connected',
                'message' => 'Facebook account linked and pages synced successfully.',
                'context' => ['facebook_id' => $user->facebook_id],
            ]);

            return $this->success([
                'facebook_connected' => true,
                'pages' => $this->pagesFor($user->id),
            ], 'Facebook account linked and Pages synced successfully.');
        } catch (\App\Exceptions\MetaApiException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Meta connect failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to connect Facebook account. Please try again.', 422);
        }
    }

    public function pages(Request $request)
    {
        return $this->success([
            'facebook_connected' => (bool) $request->user()->facebook_id,
            'pages' => $this->pagesFor($request->user()->id),
        ]);
    }

    public function disconnect(Request $request)
    {
        $user = $request->user();

        $user->metaPages()->update(['status' => 'disconnected']);
        $user->facebook_page_id = null;
        $user->facebook_page_name = null;
        $user->facebook_page_access_token = null;
        $user->facebook_token_expired = false;
        $user->save();

        MetaSyncLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'type' => 'info',
            'event_type' => 'disconnected',
            'message' => 'Facebook account disconnected. Associated pages marked as disconnected.',
            'context' => ['facebook_id' => $user->facebook_id],
        ]);

        return $this->success([
            'facebook_connected' => false,
            'pages' => $this->pagesFor($user->id),
        ], 'Facebook account disconnected successfully.');
    }

    private function exchangeCode(string $code, string $redirectUri): string
    {
        $response = Http::timeout(15)->get($this->graphUrl('/oauth/access_token'), [
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => $redirectUri,
            'client_secret' => config('services.meta.app_secret'),
            'code' => $code,
        ]);

        if (!$response->successful() || !$response->json('access_token')) {
            throw new \Exception('Failed to exchange the Facebook authorization code.');
        }

        return $response->json('access_token');
    }

    private function resolveRedirectUri(?string $requested): string
    {
        $fallback = config('services.meta.redirect');

        if (!$requested) {
            return $fallback ?: URL::route('connect.facebook.callback', [], false);
        }

        $allowedOrigins = array_filter(array_merge(
            (array) config('cors.allowed_origins'),
            [(string) config('app.frontend_url'), (string) config('app.url')]
        ));

        $origin = parse_url($requested, PHP_URL_SCHEME) . '://' . parse_url($requested, PHP_URL_HOST)
            . (parse_url($requested, PHP_URL_PORT) ? ':' . parse_url($requested, PHP_URL_PORT) : '');

        if (!in_array($origin, $allowedOrigins, true) && $origin !== $this->originOf($fallback)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'redirect_uri' => 'The requested redirect URI is not allowed.',
            ]);
        }

        return $requested;
    }

    private function consumeState(Request $request, array $validated): ?string
    {
        if (empty($validated['state'])) {
            return null;
        }

        $cached = Cache::pull($this->stateKey($request->user()->id, $validated['state']));

        return is_array($cached) && !empty($cached['redirect_uri']) ? $cached['redirect_uri'] : null;
    }

    private function originOf(?string $url): string
    {
        if (!$url) {
            return '';
        }

        return parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST)
            . (parse_url($url, PHP_URL_PORT) ? ':' . parse_url($url, PHP_URL_PORT) : '');
    }

    private function pagesFor(int $userId): array
    {
        return MetaPage::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (MetaPage $page) => [
                'id' => $page->id,
                'page_id' => $page->page_id,
                'page_name' => $page->page_name,
                'status' => $page->status,
                'token_last_synced_at' => $page->token_last_synced_at?->toIso8601String(),
            ])
            ->all();
    }

    private function stateKey(int $userId, string $state): string
    {
        return "meta_oauth_state:{$userId}:{$state}";
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/' . config('services.meta.graph_version', 'v19.0') . $path;
    }
}
