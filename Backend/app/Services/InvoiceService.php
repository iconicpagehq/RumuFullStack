<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

use App\Services\Billing\InvoiceSettingsService;

class InvoiceService
{
    protected InvoicePdfService $pdfService;
    protected InvoiceEmailService $emailService;
    protected InvoiceSettingsService $settings;

    public function __construct(InvoicePdfService $pdfService, InvoiceEmailService $emailService, InvoiceSettingsService $settings)
    {
        $this->pdfService = $pdfService;
        $this->emailService = $emailService;
        $this->settings = $settings;
    }

    /**
     * Generate an invoice for a newly created subscription and payment.
     * Must be called within a database transaction.
     */
    public function generateInvoice(Subscription $subscription, Payment $payment, array $billingData): Invoice
    {
        $subtotal = $billingData['price'];
        $gst = $billingData['gst'] ?? 0;
        $discount = $billingData['discount'] ?? 0;
        $total = $subtotal + $gst - $discount;

        $invoiceNumber = $this->generateInvoiceNumber();

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'subtotal' => $subtotal,
            'tax' => $gst,
            'discount' => $discount,
            'total' => $total,
            'status' => 'paid',
        ]);

        // Create Invoice Item
        $invoice->invoiceItems()->create([
            'description' => "{$subscription->plan_name} Plan - {$subscription->duration_months} Months",
            'quantity' => 1,
            'unit_price' => $subtotal,
            'total' => $subtotal,
        ]);

        // Dispatch Job to generate PDF and Email (asynchronously)
        \App\Jobs\GenerateInvoiceJob::dispatch($invoice);

        return $invoice;
    }

    /**
     * Generate sequential invoice numbers like RUMU-YYYY-XXXXXX
     * Uses lockForUpdate to ensure atomicity within a transaction.
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = $this->settings->getPrefix();
        $startNumber = $this->settings->getStartNumber();

        // Get the latest invoice number for this prefix, locked for update to prevent race conditions
        $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        if (!$lastInvoice) {
            $nextNumber = $startNumber;
        } else {
            // Extract the number part
            $lastNumberString = str_replace($prefix, '', $lastInvoice->invoice_number);
            $lastNumber = (int) $lastNumberString;
            $nextNumber = max($lastNumber + 1, $startNumber);
        }

        // Pad it slightly if it's small, otherwise just use the number
        $paddedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        
        return $prefix . (strlen((string)$nextNumber) > 6 ? $nextNumber : $paddedNumber);
    }
}
