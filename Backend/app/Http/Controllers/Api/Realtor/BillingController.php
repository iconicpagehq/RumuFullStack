<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SubscriptionResource;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BillingController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $currentSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'pending', 'expired'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending' THEN 1 WHEN 'expired' THEN 2 ELSE 3 END")
            ->latest()
            ->first();

        $subscriptions = $user->subscriptions()
            ->latest()
            ->paginate(10, ['*'], 'subs_page');

        $payments = $user->payments()
            ->latest('payment_date')
            ->paginate(10, ['*'], 'payments_page');

        $invoices = $user->invoices()
            ->latest()
            ->paginate(10, ['*'], 'invoices_page');

        $summary = [
            'total_paid' => (float) $user->payments()->where('payment_status', 'completed')->sum('amount'),
            'pending_payments' => $user->payments()->where('payment_status', 'pending')->count(),
            'total_invoices' => $user->invoices()->count(),
            'last_payment_at' => $user->payments()->latest('payment_date')->first()?->payment_date?->toIso8601String(),
        ];

        return $this->success([
            'current_subscription' => $currentSubscription
                ? new SubscriptionResource($currentSubscription)
                : null,
            'plan' => $user->plan ? [
                'id' => $user->plan->id,
                'key' => $user->plan->key,
                'name' => $user->plan->name,
            ] : null,
            'summary' => $summary,
            'subscriptions' => SubscriptionResource::collection($subscriptions)->response()->getData(true),
            'payments' => PaymentResource::collection($payments)->response()->getData(true),
            'invoices' => InvoiceResource::collection($invoices)->response()->getData(true),
            'invoices_page' => $invoices->currentPage(),
            'subscriptions_page' => $subscriptions->currentPage(),
            'payments_page' => $payments->currentPage(),
        ]);
    }

    public function showInvoice(Request $request, int $id)
    {
        $invoice = $request->user()->invoices()
            ->with(['invoiceItems', 'subscription', 'payment'])
            ->findOrFail($id);

        Log::info('Customer viewed invoice', [
            'invoice_id' => $invoice->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->success(new InvoiceResource($invoice));
    }

    public function downloadInvoice(Request $request, int $id, InvoicePdfService $pdfService)
    {
        $invoice = $request->user()->invoices()->findOrFail($id);

        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            try {
                $path = $pdfService->generateAndStorePdf($invoice);
                $invoice->update(['pdf_path' => $path]);
            } catch (\Exception $e) {
                Log::error('Customer invoice download failed - PDF regeneration error', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->error('Unable to generate the invoice PDF. Please contact support.', 500);
            }
        }

        Log::info('Customer downloaded invoice', [
            'invoice_id' => $invoice->id,
            'user_id' => $request->user()->id,
        ]);

        return Storage::disk('public')->download(
            $invoice->pdf_path,
            'Invoice-' . $invoice->invoice_number . '.pdf'
        );
    }
}
