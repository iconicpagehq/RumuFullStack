<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Http\Requests\Admin\IndexInvoiceRequest;
use App\Http\Requests\Admin\ResendInvoiceRequest;
use App\Services\InvoicePdfService;
use App\Services\Billing\BillingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices with advanced filtering and search.
     */
    public function index(IndexInvoiceRequest $request)
    {
        $query = Invoice::with(['user', 'subscription', 'payment'])->latest();

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('payment', function($pq) use ($search) {
                      $pq->where('transaction_id', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Date Range Filter
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Plan Filter
        if ($plan = $request->input('plan')) {
            $query->whereHas('subscription', function($sq) use ($plan) {
                $sq->where('plan_name', $plan);
            });
        }

        // Payment Method Filter
        if ($paymentMethod = $request->input('payment_method')) {
            $query->whereHas('payment', function($pq) use ($paymentMethod) {
                $pq->where('payment_method', $paymentMethod);
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        Log::info('Invoices listed/searched', [
            'admin_id' => auth()->id(),
            'filters' => $request->validated()
        ]);

        return view('admin.billing.invoices.index', compact('invoices'));
    }

    /**
     * Display the specified invoice details.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'subscription', 'payment', 'invoiceItems']);

        Log::info('Invoice viewed', [
            'admin_id' => auth()->id(),
            'invoice_id' => $invoice->id
        ]);

        return view('admin.billing.invoices.show', compact('invoice'));
    }

    /**
     * Download the invoice PDF.
     */
    public function download(Invoice $invoice, InvoicePdfService $pdfService)
    {
        $response = $pdfService->downloadPdf($invoice);

        if (!$response) {
            return redirect()->back()->with('error', 'The PDF file for this invoice could not be found.');
        }

        return $response;
    }

    /**
     * Preview the invoice PDF in browser (for printing).
     */
    public function preview(Invoice $invoice, InvoicePdfService $pdfService)
    {
        $response = $pdfService->streamPdf($invoice);

        if (!$response) {
            return redirect()->back()->with('error', 'The PDF file for this invoice could not be found.');
        }

        return $response;
    }

    /**
     * Resend the invoice email.
     */
    public function resend(ResendInvoiceRequest $request, Invoice $invoice, BillingNotificationService $notificationService)
    {
        $notificationService->notify(
            $invoice->user,
            'invoice_emailed',
            $invoice->subscription,
            $invoice->payment,
            $invoice,
            'email'
        );

        Log::info('Invoice resend dispatched', [
            'admin_id' => auth()->id(),
            'invoice_id' => $invoice->id
        ]);

        return redirect()->back()->with('success', 'Invoice email has been queued for resending.');
    }
}
