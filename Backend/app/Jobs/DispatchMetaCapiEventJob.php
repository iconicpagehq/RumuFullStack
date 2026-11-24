<?php

namespace App\Jobs;

use App\Models\MetaCapiEvent;
use App\Models\MetaSyncLog;
use App\Services\MetaCapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchMetaCapiEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    
    // Exponential backoff
    public function backoff(): array
    {
        return [15, 60, 300];
    }

    protected int $capiEventId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $capiEventId)
    {
        $this->capiEventId = $capiEventId;
    }

    /**
     * Execute the job.
     */
    public function handle(MetaCapiService $service): void
    {
        $capiEvent = MetaCapiEvent::with(['lead', 'campaign.metaCapiSettings'])->find($this->capiEventId);

        if (!$capiEvent || !$capiEvent->lead || !$capiEvent->campaign || !$capiEvent->campaign->metaCapiSettings) {
            return;
        }

        $lead = $capiEvent->lead;
        $settings = $capiEvent->campaign->metaCapiSettings;

        // Build Payload
        $payload = $service->buildPayload($lead, $capiEvent->event_name, $capiEvent->event_id);
        
        // Send API Request
        $response = $service->sendEvent($payload, $settings);

        // Update Audit Trail
        $capiEvent->update([
            'status' => $response['successful'] ? 'success' : 'failed',
            'response' => $response['json'],
            'sent_at' => now(),
        ]);

        // Handle Errors and Retries
        if (!$response['successful']) {
            if ($response['status'] === 429) {
                // Rate limited, let it retry
                $this->release($this->backoff()[$this->attempts() - 1] ?? 300);
                return;
            }
            
            // If we've exhausted all attempts or got a hard error, log to dashboard
            if ($this->attempts() >= $this->tries) {
                MetaSyncLog::create([
                    'user_id' => $capiEvent->campaign->user_id,
                    'campaign_id' => $capiEvent->campaign_id,
                    'type' => 'error',
                    'event_type' => 'capi_error',
                    'message' => "Failed to send CAPI event '{$capiEvent->event_name}' to Meta after multiple attempts.",
                ]);
            } else {
                // Throw exception to trigger standard queue retry
                throw new \Exception("Meta CAPI Error: " . json_encode($response['json']));
            }
        }
    }
}
