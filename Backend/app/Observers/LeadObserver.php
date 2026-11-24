<?php

namespace App\Observers;

use App\Events\LeadStatusUpdated;
use App\Models\Lead;

class LeadObserver
{
    public bool $afterCommit = true;

    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        event(new LeadStatusUpdated($lead));
    }

    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $lead): void
    {
        if ($lead->wasChanged('status')) {
            event(new LeadStatusUpdated($lead));
        }
    }
}
