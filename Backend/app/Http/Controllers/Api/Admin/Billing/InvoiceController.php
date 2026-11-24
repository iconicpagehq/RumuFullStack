<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\Billing\BillingNotificationService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends ApiController
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:paid,draft,cancelled,refunded',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'plan' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ]);

        $query = Invoice::with(['user', 'subscription', 'payment'])->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('payment', function ($paymentQuery) use ($search) {
                        $paymentQuery->where('transaction_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($plan = $request->input('plan')) {
            $query->whereHas('subscription', fn ($subscriptionQuery) => $subscriptionQuery->where('plan_name', $plan));
        }

        if ($paymentMethod = $request->input('payment_method')) {
            $query->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('payment_method', $paymentMethod));
        }

        $invoices = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        return $this->paginated(InvoiceResource::collection($invoices));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'subscription', 'payment', 'invoiceItems']);

        return $this->success(new InvoiceResource($invoice));
    }

    public function download(Invoice $invoice, InvoicePdfService $pdfService)
    {
        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            try {
                $path = $pdfService->generateAndStorePdf($invoice);
                $invoice->update(['pdf_path' => $path]);
            } catch (\Throwable $e) {
                return $this->error('The PDF for this invoice could not be generated.', 500);
            }
        }

        return Storage::disk('public')->download(
            $invoice->pdf_path,
            'Invoice-' . $invoice->invoice_number . '.pdf'
        );
    }

    public function resend(Invoice $invoice, BillingNotificationService $notifications)
    {
        $invoice->load('user', 'subscription', 'payment');

        if (!$invoice->user) {
            return $this->error('Invoice customer not found.', 404);
        }

        $notifications->notify(
            $invoice->user,
            'invoice_emailed',
            $invoice->subscription,
            $invoice->payment,
            $invoice,
            'email'
        );

        return $this->success(null, 'Invoice email has been queued for resending.');
    }
}
