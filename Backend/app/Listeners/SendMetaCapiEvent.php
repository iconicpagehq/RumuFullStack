<?php

namespace App\Listeners;

use App\Events\LeadStatusUpdated;
use App\Jobs\DispatchMetaCapiEventJob;
use App\Models\MetaCapiEvent;
use Illuminate\Support\Str;

class SendMetaCapiEvent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LeadStatusUpdated $event): void
    {
        $lead = $event->lead;
        
        // Ensure relationships are available
        $campaign = $lead->campaign()->first();

        if (!$campaign) {
            return;
        }

        $settings = $campaign->metaCapiSettings()->first();

        if (!$settings || !$settings->is_active) {
            return;
        }

        $mapping = $settings->status_mapping ?? [];
        
        $metaEventName = $mapping[$lead->status] ?? null;

        if (!$metaEventName) {
            return;
        }

        // Create the audit log and generate the deterministic event_id
        $capiEvent = MetaCapiEvent::create([
            'lead_id' => $lead->id,
            'campaign_id' => $campaign->id,
            'event_name' => $metaEventName,
            'event_id' => Str::uuid()->toString(),
            'status' => 'pending',
        ]);

        DispatchMetaCapiEventJob::dispatch($capiEvent->id);
    }
}
