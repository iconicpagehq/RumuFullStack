<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MetaLeadApiService;
use App\Services\MetaLeadImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookLeadJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $pageId,
        public string $formId,
        public string $leadId,
    ) {
        $this->onQueue('meta-leads');
    }

    /**
     * Execute the job.
     */
    public function handle(MetaLeadApiService $metaApi, MetaLeadImportService $metaImport): void
    {
        $startTime = microtime(true);

        Log::info('Meta Webhook Job Started processing lead', [
            'page_id' => $this->pageId,
            'form_id' => $this->formId,
            'lead_id' => $this->leadId,
            'attempt' => $this->attempts(),
        ]);

        $metaPage = \App\Models\MetaPage::where('page_id', $this->pageId)->first();
        if (!$metaPage) {
            Log::warning('Meta Webhook Job: MetaPage not found for Page ID', [
                'page_id' => $this->pageId,
            ]);
            $this->fail(new \Exception('MetaPage not found'));
            return;
        }

        try {
            $result = $metaImport->importSingleLeadFromWebhook($metaPage, $this->formId, $this->leadId, $metaApi);

            $duration = round((microtime(true) - $startTime) * 1000); // ms
            Log::info('Meta Webhook Job Completed processing lead', [
                'lead_id' => $this->leadId,
                'result' => $result,
                'duration_ms' => $duration,
            ]);
        } catch (\App\Exceptions\MetaApiException $e) {
            $duration = round((microtime(true) - $startTime) * 1000); // ms
            Log::error('Meta Webhook Job encountered Meta API Exception', [
                'lead_id' => $this->leadId,
                'error' => $e->getMessage(),
                'transient' => $e->isTransient(),
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
            ]);

            if (!$e->isTransient()) {
                $this->fail($e);
            } else {
                throw $e; // Let it be retried by the queue
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Meta Webhook Job Failed permanently', [
            'page_id' => $this->pageId,
            'form_id' => $this->formId,
            'lead_id' => $this->leadId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
