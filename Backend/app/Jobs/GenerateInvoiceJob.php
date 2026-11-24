<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Services\InvoiceEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice;

    /**
     * Create a new job instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     */
    public function handle(InvoicePdfService $pdfService, InvoiceEmailService $emailService): void
    {
        try {
            // Generate PDF
            $pdfPath = $pdfService->generateAndStorePdf($this->invoice);
            
            // Update invoice with PDF path
            $this->invoice->update(['pdf_path' => $pdfPath]);
            
            Log::info('Invoice PDF generated (Queued)', [
                'invoice_id' => $this->invoice->id,
                'path' => $pdfPath
            ]);

            // Dispatch Email Job
            $emailService->dispatchEmailJob($this->invoice);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice PDF (Queued)', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
