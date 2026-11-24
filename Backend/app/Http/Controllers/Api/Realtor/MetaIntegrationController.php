<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\MetaCapiEvent;
use App\Models\MetaSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetaIntegrationController extends ApiController
{
    public function show(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $user = $request->user();

        $metaPages = $user->metaPages()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($page) => [
                'id' => $page->id,
                'page_id' => $page->page_id,
                'page_name' => $page->page_name,
                'status' => $page->status,
                'token_last_synced_at' => $page->token_last_synced_at?->toIso8601String(),
                'consecutive_failures' => $page->consecutive_failures,
                'last_successful_api_request_at' => $page->last_successful_api_request_at?->toIso8601String(),
                'last_failed_api_request_at' => $page->last_failed_api_request_at?->toIso8601String(),
            ]);

        $metaCampaignForm = DB::table('meta_campaign_forms')
            ->where('campaign_id', $campaign->id)
            ->first();

        $selectedPage = null;
        if ($metaCampaignForm) {
            $selectedPage = $metaPages->firstWhere('page_id', $metaCampaignForm->page_id);
        }

        $formInfo = null;
        if ($metaCampaignForm) {
            $formInfo = [
                'form_name' => $metaCampaignForm->form_name,
                'form_id' => $metaCampaignForm->form_id,
                'page_id' => $metaCampaignForm->page_id,
                'last_webhook' => $metaCampaignForm->last_webhook_received_at,
                'last_sync' => $metaCampaignForm->last_manual_sync_at,
            ];
        }

        $totalImported = $campaign->leads()->where('source', 'meta_ads')->count();
        $lastImport = $campaign->leads()->where('source', 'meta_ads')->latest()->first()?->created_at;

        $webhookHealth = 'Inactive';
        if ($metaCampaignForm && $metaCampaignForm->last_webhook_received_at) {
            $lastWebhook = \Carbon\Carbon::parse($metaCampaignForm->last_webhook_received_at);
            if ($lastWebhook->diffInDays(now()) <= 7) {
                $webhookHealth = 'Active';
            }
        }

        $apiHealth = null;
        if ($selectedPage) {
            $apiHealth = [
                'status' => $selectedPage['status'],
                'consecutive_failures' => $selectedPage['consecutive_failures'],
                'last_success' => $selectedPage['last_successful_api_request_at'],
                'last_failure' => $selectedPage['last_failed_api_request_at'],
            ];
        }

        $logs = MetaSyncLog::where('user_id', $user->id)
            ->where(function ($query) use ($campaign) {
                $query->where('campaign_id', $campaign->id)
                    ->orWhereNull('campaign_id');
            })
            ->orderBy('created_at', 'desc')
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

        $capiSettings = $campaign->metaCapiSettings;

        $lastCapiEvent = MetaCapiEvent::where('campaign_id', $campaign->id)
            ->latest('sent_at')
            ->first();

        return $this->success([
            'campaign' => new CampaignResource($campaign),
            'facebook_connected' => (bool) ($user->facebook_id || $metaPages->isNotEmpty()),
            'meta_configured' => (bool) config('services.meta.app_id'),
            'pages' => $metaPages,
            'selected_page_id' => $selectedPage['page_id'] ?? null,
            'form' => $formInfo,
            'stats' => [
                'total_imported' => $totalImported,
                'last_import_time' => $lastImport?->toIso8601String(),
            ],
            'webhook_health' => $webhookHealth,
            'api_health' => $apiHealth,
            'logs' => $logs,
            'capi_settings' => $capiSettings ? [
                'pixel_id' => $capiSettings->pixel_id,
                'test_event_code' => $capiSettings->test_event_code,
                'is_active' => $capiSettings->is_active,
                'status_mapping' => $capiSettings->status_mapping ?? [],
                'has_access_token' => !empty($capiSettings->access_token),
            ] : null,
            'last_capi_event' => $lastCapiEvent ? [
                'id' => $lastCapiEvent->id,
                'event_name' => $lastCapiEvent->event_name,
                'status' => $lastCapiEvent->status,
                'sent_at' => $lastCapiEvent->sent_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function disconnectForm(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        DB::table('meta_campaign_forms')->where('campaign_id', $campaign->id)->delete();
        $campaign->update(['meta_lead_form_id' => null]);

        MetaSyncLog::create([
            'user_id' => $request->user()->id,
            'campaign_id' => $campaign->id,
            'type' => 'info',
            'event_type' => 'disconnected',
            'message' => 'Lead Form disconnected from campaign.',
        ]);

        return $this->success(null, 'Meta Lead Form disconnected successfully.');
    }

    public function saveCapiSettings(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

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

        MetaSyncLog::create([
            'user_id' => $request->user()->id,
            'campaign_id' => $campaign->id,
            'type' => 'info',
            'event_type' => 'capi_configured',
            'message' => 'Meta Conversions API settings updated.',
        ]);

        return $this->success([
            'pixel_id' => $settings->pixel_id,
            'test_event_code' => $settings->test_event_code,
            'is_active' => $settings->is_active,
            'status_mapping' => $settings->status_mapping ?? [],
            'has_access_token' => !empty($settings->access_token),
        ], 'Meta Conversions API settings saved successfully.');
    }
}
