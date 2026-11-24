<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Models\Campaign;
use App\Models\MetaCapiEvent;
use App\Models\MetaPage;
use App\Models\MetaSyncLog;
use App\Services\EnterpriseAuditService;
use App\Services\EnterpriseLimitService;
use App\Services\MetaLeadApiService;
use App\Services\MetaLeadImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnterpriseMetaController extends EnterpriseApiController
{
    private const SCOPES = [
        'pages_show_list',
        'pages_manage_metadata',
        'leads_retrieval',
    ];

    public function pages(Request $request, EnterpriseLimitService $limits)
    {
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        return $this->success([
            'meta_configured' => (bool) config('services.meta.app_id'),
            'pages' => $this->pagesFor($request),
        ]);
    }

    public function connectUrl(Request $request, EnterpriseLimitService $limits)
    {
        $this->requireRole($request, ['admin']);
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        if (!config('services.meta.app_id')) {
            return $this->error('Meta integration is not configured on this server.', 503);
        }

        $redirectUri = $this->resolveRedirectUri($request->query('redirect_uri'));
        $state = Str::random(40);
        $enterprise = $this->enterprise($request);

        Cache::put(
            $this->stateKey($enterprise->id, $request->user()->id, $state),
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

    public function connect(
        Request $request,
        MetaLeadApiService $metaApi,
        EnterpriseLimitService $limits,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);
        $limits->requireFeature($enterprise, 'meta_integrations');

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
            $metaApi->syncUserPagesFromToken($request->user(), $userAccessToken, $enterprise->id);

            MetaSyncLog::create([
                'user_id' => $request->user()->id,
                'campaign_id' => null,
                'type' => 'success',
                'event_type' => 'connected',
                'message' => 'Enterprise Facebook Pages synced successfully.',
                'context' => ['enterprise_id' => $enterprise->id],
            ]);

            $audit->log($enterprise, $request->user(), 'meta.connected', null, 'Enterprise Meta pages connected');

            return $this->success([
                'pages' => $this->pagesFor($request),
            ], 'Facebook Pages connected successfully.');
        } catch (\App\Exceptions\MetaApiException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Enterprise Meta connect failed', [
                'enterprise_id' => $enterprise->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to connect the Facebook account. Please try again.', 422);
        }
    }

    public function disconnect(
        Request $request,
        EnterpriseLimitService $limits,
        EnterpriseAuditService $audit,
    ) {
        $this->requireRole($request, ['admin']);
        $enterprise = $this->enterprise($request);
        $limits->requireFeature($enterprise, 'meta_integrations');

        MetaPage::where('enterprise_id', $enterprise->id)->update(['status' => 'disconnected']);

        DB::table('meta_campaign_forms')
            ->whereIn('campaign_id', Campaign::where('enterprise_id', $enterprise->id)->select('id'))
            ->delete();

        $audit->log($enterprise, $request->user(), 'meta.disconnected', null, 'Enterprise Meta pages disconnected');

        return $this->success(['pages' => $this->pagesFor($request)], 'Facebook Pages disconnected.');
    }

    public function show(Request $request, Campaign $campaign, EnterpriseLimitService $limits)
    {
        $this->ensureCampaignVisible($request, $campaign);
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        $pages = $this->pagesFor($request);
        $mapping = DB::table('meta_campaign_forms')->where('campaign_id', $campaign->id)->first();

        $selectedPage = $mapping
            ? collect($pages)->firstWhere('page_id', $mapping->page_id)
            : null;

        $settings = $campaign->metaCapiSettings;
        $lastEvent = MetaCapiEvent::where('campaign_id', $campaign->id)->latest('sent_at')->first();

        $logs = MetaSyncLog::where('campaign_id', $campaign->id)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'type' => $log->type,
                'event_type' => $log->event_type,
                'message' => $log->message,
                'context' => $log->context,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'meta_configured' => (bool) config('services.meta.app_id'),
            'pages' => $pages,
            'selected_page_id' => $mapping->page_id ?? null,
            'form' => $mapping ? [
                'page_id' => $mapping->page_id,
                'form_id' => $mapping->form_id,
                'form_name' => $mapping->form_name,
                'last_webhook' => $mapping->last_webhook_received_at,
                'last_sync' => $mapping->last_manual_sync_at,
            ] : null,
            'selected_page' => $selectedPage,
            'stats' => [
                'total_imported' => $campaign->leads()->where('source', 'meta_ads')->count(),
                'last_import_time' => $campaign->leads()->where('source', 'meta_ads')->latest()->first()?->created_at?->toIso8601String(),
            ],
            'capi_settings' => $settings ? [
                'pixel_id' => $settings->pixel_id,
                'test_event_code' => $settings->test_event_code,
                'is_active' => (bool) $settings->is_active,
                'status_mapping' => $settings->status_mapping ?? [],
                'has_access_token' => !empty($settings->access_token),
            ] : null,
            'last_capi_event' => $lastEvent ? [
                'id' => $lastEvent->id,
                'event_name' => $lastEvent->event_name,
                'status' => $lastEvent->status,
                'sent_at' => $lastEvent->sent_at?->toIso8601String(),
            ] : null,
            'logs' => $logs,
        ]);
    }

    public function forms(Request $request, Campaign $campaign, MetaLeadApiService $metaApi, EnterpriseLimitService $limits)
    {
        $this->ensureCampaignVisible($request, $campaign);
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        $page = $this->resolvePage($request, $campaign, $request->query('page_id'));
        if (!$page) {
            return $this->error('Connect a Facebook Page and map it to this campaign first.', 422);
        }

        try {
            return $this->success([
                'page' => ['page_id' => $page->page_id, 'page_name' => $page->page_name],
                'forms' => $metaApi->listLeadForms($page),
            ]);
        } catch (\App\Exceptions\MetaApiException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function saveMapping(
        Request $request,
        Campaign $campaign,
        EnterpriseLimitService $limits,
        EnterpriseAuditService $audit,
    ) {
        $this->ensureCampaignVisible($request, $campaign);
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        $validated = $request->validate([
            'page_id' => 'required|string|max:100',
            'form_id' => 'required|string|max:100',
            'form_name' => 'nullable|string|max:255',
        ]);

        $page = MetaPage::where('enterprise_id', $this->enterprise($request)->id)
            ->where('page_id', $validated['page_id'])
            ->where('status', 'connected')
            ->first();

        if (!$page) {
            throw ValidationException::withMessages([
                'page_id' => ['Select a connected Facebook Page from this enterprise.'],
            ]);
        }

        DB::table('meta_campaign_forms')->updateOrInsert(
            ['campaign_id' => $campaign->id],
            [
                'page_id' => $page->page_id,
                'form_id' => $validated['form_id'],
                'form_name' => $validated['form_name'] ?? 'Mapped Form',
                'updated_at' => now(),
            ]
        );

        $campaign->update(['meta_lead_form_id' => $validated['form_id']]);

        $audit->log($this->enterprise($request), $request->user(), 'meta.mapping_saved', $campaign, 'Meta lead form mapped', [
            'page_id' => $page->page_id,
            'form_id' => $validated['form_id'],
        ]);

        return $this->success(null, 'Meta lead form mapped to this campaign.');
    }

    public function disconnectForm(Request $request, Campaign $campaign, EnterpriseLimitService $limits)
    {
        $this->ensureCampaignVisible($request, $campaign);
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        DB::table('meta_campaign_forms')->where('campaign_id', $campaign->id)->delete();
        $campaign->update(['meta_lead_form_id' => null]);

        return $this->success(null, 'Meta lead form disconnected from this campaign.');
    }

    public function saveCapi(
        Request $request,
        Campaign $campaign,
        EnterpriseLimitService $limits,
        EnterpriseAuditService $audit,
    ) {
        $this->ensureCampaignVisible($request, $campaign);
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        $validated = $request->validate([
            'pixel_id' => 'required|string|max:255',
            'access_token' => 'nullable|string',
            'test_event_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'status_mapping' => 'nullable|array',
            'status_mapping.*' => 'nullable|string',
        ]);

        $settings = $campaign->metaCapiSettings()->firstOrNew();
        $settings->pixel_id = $validated['pixel_id'];

        if (!empty($validated['access_token'])) {
            $settings->access_token = $validated['access_token'];
        } elseif (!$settings->exists) {
            return $this->error('Access token is required for initial setup.', 422);
        }

        $settings->test_event_code = $validated['test_event_code'] ?? null;
        $settings->is_active = $request->boolean('is_active');
        $settings->status_mapping = array_filter($validated['status_mapping'] ?? []);
        $settings->save();

        $audit->log($this->enterprise($request), $request->user(), 'meta.capi_updated', $campaign, 'Meta Conversions API settings updated');

        return $this->success([
            'pixel_id' => $settings->pixel_id,
            'test_event_code' => $settings->test_event_code,
            'is_active' => (bool) $settings->is_active,
            'status_mapping' => $settings->status_mapping ?? [],
            'has_access_token' => !empty($settings->access_token),
        ], 'Meta Conversions API settings saved.');
    }

    public function sync(
        Request $request,
        Campaign $campaign,
        MetaLeadImportService $imports,
        MetaLeadApiService $metaApi,
        EnterpriseLimitService $limits,
        EnterpriseAuditService $audit,
    ) {
        $this->ensureCampaignVisible($request, $campaign);
        $limits->requireFeature($this->enterprise($request), 'meta_integrations');

        $validated = $request->validate([
            'page_id' => 'nullable|string|max:100',
            'form_id' => 'nullable|string|max:100',
        ]);

        $mapping = DB::table('meta_campaign_forms')->where('campaign_id', $campaign->id)->first();
        $formId = $validated['form_id'] ?? $mapping->form_id ?? null;

        if (!$formId) {
            throw ValidationException::withMessages(['form_id' => ['Map a lead form to this campaign before syncing.']]);
        }

        $page = $this->resolvePage($request, $campaign, $validated['page_id'] ?? null);
        if (!$page) {
            throw ValidationException::withMessages(['page_id' => ['Connect a Facebook Page before syncing.']]);
        }

        $result = $imports->syncFromApi($campaign, $campaign->user, $formId, $page, $metaApi);

        $audit->log($this->enterprise($request), $request->user(), 'meta.synced', $campaign, 'Meta leads synced', [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ]);

        return $this->success($result, $result['message']);
    }

    private function resolvePage(Request $request, Campaign $campaign, ?string $pageId = null): ?MetaPage
    {
        $query = MetaPage::where('enterprise_id', $this->enterprise($request)->id)
            ->where('status', 'connected');

        if ($pageId) {
            $query->where('page_id', $pageId);
        } else {
            $mapping = DB::table('meta_campaign_forms')->where('campaign_id', $campaign->id)->first();
            if ($mapping) {
                $query->where('page_id', $mapping->page_id);
            } else {
                return null;
            }
        }

        return $query->first();
    }

    private function ensureCampaignVisible(Request $request, Campaign $campaign): void
    {
        $membership = $this->membership($request);
        abort_if((int) $campaign->enterprise_id !== (int) $membership->enterprise_id, 404);

        if ($membership->isAdmin()) {
            return;
        }

        if ($membership->isManager()) {
            $visible = in_array((int) $campaign->team_id, $this->teamScopeIds($request), true)
                || (int) $campaign->user_id === (int) $membership->user_id;
            abort_if(!$visible, 403, 'This campaign is outside your scope.');
            return;
        }

        abort(403, 'You cannot manage Meta integrations.');
    }

    private function pagesFor(Request $request): array
    {
        return MetaPage::where('enterprise_id', $this->enterprise($request)->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MetaPage $page) => [
                'id' => $page->id,
                'page_id' => $page->page_id,
                'page_name' => $page->page_name,
                'status' => $page->status,
                'token_last_synced_at' => $page->token_last_synced_at?->toIso8601String(),
                'consecutive_failures' => $page->consecutive_failures,
            ])
            ->all();
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
            return $fallback ?: config('app.url');
        }

        $allowedOrigins = array_filter(array_merge(
            (array) config('cors.allowed_origins'),
            [(string) config('app.frontend_url'), (string) config('app.url')]
        ));

        $origin = $this->originOf($requested);

        if (!in_array($origin, $allowedOrigins, true) && $origin !== $this->originOf($fallback)) {
            throw ValidationException::withMessages([
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

        $cached = Cache::pull($this->stateKey($this->enterprise($request)->id, $request->user()->id, $validated['state']));

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

    private function stateKey(int $enterpriseId, int $userId, string $state): string
    {
        return "enterprise_meta_oauth_state:{$enterpriseId}:{$userId}:{$state}";
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/' . config('services.meta.graph_version', 'v19.0') . $path;
    }
}
