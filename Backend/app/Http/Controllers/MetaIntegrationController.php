<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MetaIntegrationController extends Controller
{
    /**
     * Show the Meta Integration Management Dashboard for a campaign.
     */
    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $user = Auth::user();

        // Get all meta pages for the user
        $metaPages = $user->metaPages()->orderBy('created_at', 'desc')->get();

        // Find the currently mapped form and page
        $metaCampaignForm = DB::table('meta_campaign_forms')
            ->where('campaign_id', $campaign->id)
            ->first();

        $selectedPage = null;
        if ($metaCampaignForm) {
            $selectedPage = $metaPages->where('page_id', $metaCampaignForm->page_id)->first();
        }

        $formInfo = null;
        if ($metaCampaignForm) {
            $formInfo = [
                'form_name' => $metaCampaignForm->form_name,
                'form_id' => $metaCampaignForm->form_id,
                'last_webhook' => $metaCampaignForm->last_webhook_received_at,
                'last_sync' => $metaCampaignForm->last_manual_sync_at,
            ];
        }

        // Integration Statistics
        $totalImported = $campaign->leads()->where('source', 'meta_ads')->count();
        $lastImport = $campaign->leads()->where('source', 'meta_ads')->latest()->first()?->created_at;

        $stats = [
            'total_imported' => $totalImported,
            'last_import_time' => $lastImport,
        ];

        // Webhook Health
        $webhookHealth = 'Inactive';
        if ($metaCampaignForm && $metaCampaignForm->last_webhook_received_at) {
            $lastWebhook = \Carbon\Carbon::parse($metaCampaignForm->last_webhook_received_at);
            if ($lastWebhook->diffInDays(now()) <= 7) {
                $webhookHealth = 'Active';
            }
        }

        // API Health
        $apiHealth = null;
        if ($selectedPage) {
            $apiHealth = [
                'status' => $selectedPage->status,
                'consecutive_failures' => $selectedPage->consecutive_failures,
                'last_success' => $selectedPage->last_successful_api_request_at,
                'last_failure' => $selectedPage->last_failed_api_request_at,
            ];
        }

        // Activity Timeline
        $logs = \App\Models\MetaSyncLog::where('user_id', $user->id)
            ->where(function ($query) use ($campaign) {
                $query->where('campaign_id', $campaign->id)
                      ->orWhereNull('campaign_id');
            })
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        $capiSettings = $campaign->metaCapiSettings;
        $lastCapiEvent = \App\Models\MetaCapiEvent::where('campaign_id', $campaign->id)
            ->latest('sent_at')
            ->first();

        return view('campaigns.meta-integration', compact(
            'campaign',
            'metaPages',
            'selectedPage',
            'formInfo',
            'stats',
            'webhookHealth',
            'apiHealth',
            'logs',
            'capiSettings',
            'lastCapiEvent'
        ));
    }

    /**
     * Disconnect the Meta Lead Form from the campaign.
     */
    public function disconnectForm(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        DB::table('meta_campaign_forms')->where('campaign_id', $campaign->id)->delete();
        $campaign->update(['meta_lead_form_id' => null]);

        \App\Models\MetaSyncLog::create([
            'user_id' => Auth::id(),
            'campaign_id' => $campaign->id,
            'type' => 'info',
            'event_type' => 'disconnected',
            'message' => 'Lead Form disconnected from campaign.',
        ]);

        return redirect()->route('campaigns.meta-integration', $campaign)
            ->with('success', 'Meta Lead Form disconnected successfully.');
    }

    /**
     * Save Meta CAPI settings.
     */
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
        
        // Only update access token if a new one is provided
        if (!empty($validated['access_token'])) {
            $settings->access_token = $validated['access_token'];
        } elseif (!$settings->exists) {
            return back()->with('error', 'Access token is required for initial setup.');
        }

        $settings->test_event_code = $validated['test_event_code'] ?? null;
        $settings->is_active = $request->has('is_active');
        
        // Filter out empty mappings
        $mapping = array_filter($validated['status_mapping'] ?? []);
        $settings->status_mapping = $mapping;
        
        $settings->save();

        \App\Models\MetaSyncLog::create([
            'user_id' => Auth::id(),
            'campaign_id' => $campaign->id,
            'type' => 'info',
            'event_type' => 'capi_configured',
            'message' => 'Meta Conversions API settings updated.',
        ]);

        return redirect()->route('campaigns.meta-integration', $campaign)
            ->with('success', 'Meta Conversions API settings saved successfully.');
    }
}
