<?php

namespace App\Services;

use App\Models\Invoice;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class InvoiceEmailService
{
    /**
     * Dispatch the job to send the invoice email.
     */
    public function dispatchEmailJob(Invoice $invoice): void
    {
        try {
            // Load user relationship if not loaded
            $invoice->loadMissing('user');

            if (!$invoice->user || !$invoice->user->email) {
                Log::warning('InvoiceEmailService: Cannot send email, user or email missing', ['invoice_id' => $invoice->id]);
                return;
            }

            // InvoiceMail already implements ShouldQueue, so this will be queued automatically
            Mail::to($invoice->user->email)->send(new InvoiceMail($invoice));

            Log::info('Invoice email sent to queue', ['invoice_id' => $invoice->id, 'email' => $invoice->user->email]);
        } catch (\Exception $e) {
            Log::error('InvoiceEmailService: Failed to queue invoice email', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
