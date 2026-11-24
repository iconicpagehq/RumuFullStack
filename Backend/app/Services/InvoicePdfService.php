<?php

namespace App\Services;

use App\Models\Invoice;
use App\Services\Billing\BillingSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Response;

class InvoicePdfService
{
    protected $settings;

    public function __construct(BillingSettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Generate the PDF and return its path.
     */
    public function generateAndStorePdf(Invoice $invoice): string
    {
        $invoice->load(['user', 'payment', 'invoiceItems']);
        
        $settings = $this->settings;

        $pdf = Pdf::loadView('billing.invoice', compact('invoice', 'settings'));
        $fileName = "{$invoice->invoice_number}.pdf";
        $filePath = "invoices/{$fileName}";
        
        Storage::disk('public')->put($filePath, $pdf->output());

        return $filePath;
    }

    /**
     * Get the download response for the invoice PDF safely.
     */
    public function downloadPdf(Invoice $invoice)
    {
        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            Log::warning('Invoice PDF download failed: File missing', [
                'admin_id' => auth()->id(),
                'invoice_id' => $invoice->id,
                'expected_path' => $invoice->pdf_path
            ]);
            return null; // Return null to indicate failure
        }

        Log::info('Invoice PDF downloaded', [
            'admin_id' => auth()->id(),
            'invoice_id' => $invoice->id,
        ]);

        return Storage::disk('public')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }

    /**
     * Stream the invoice PDF inline (for preview/print).
     */
    public function streamPdf(Invoice $invoice)
    {
        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            Log::warning('Invoice PDF preview failed: File missing', [
                'admin_id' => auth()->id(),
                'invoice_id' => $invoice->id,
                'expected_path' => $invoice->pdf_path
            ]);
            return null;
        }

        Log::info('Invoice PDF previewed', [
            'admin_id' => auth()->id(),
            'invoice_id' => $invoice->id,
        ]);

        return Storage::disk('public')->response($invoice->pdf_path, "{$invoice->invoice_number}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $invoice->invoice_number . '.pdf"',
        ]);
    }
}
