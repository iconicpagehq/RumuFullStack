<?php

namespace App\Http\Controllers\Realtor;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    /**
     * Display the Billing Dashboard for the customer.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Active/Current Subscription
        $currentSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'pending', 'expired']) // Show whatever is most relevant
            ->orderByRaw("FIELD(status, 'active', 'pending', 'expired', 'cancelled')")
            ->latest()
            ->first();

        // Subscription History
        $subscriptions = $user->subscriptions()
            ->latest()
            ->paginate(10, ['*'], 'subs_page');

        // Payment History
        $payments = $user->payments()
            ->latest('payment_date')
            ->paginate(10, ['*'], 'payments_page');

        // Invoice History
        $invoices = $user->invoices()
            ->latest()
            ->paginate(10, ['*'], 'invoices_page');

        return view('realtor.billing.index', compact(
            'currentSubscription', 
            'subscriptions', 
            'payments', 
            'invoices'
        ));
    }

    /**
     * View Invoice Details.
     */
    public function showInvoice(Request $request, int $id)
    {
        // Strict Scoping: Only allow viewing invoices belonging to the auth user
        $invoice = $request->user()->invoices()
            ->with(['invoiceItems', 'subscription', 'payment'])
            ->findOrFail($id);

        Log::info('Customer viewed invoice', ['invoice_id' => $invoice->id, 'user_id' => $request->user()->id]);

        return view('realtor.billing.invoice', compact('invoice'));
    }

    /**
     * Download Invoice PDF.
     */
    public function downloadInvoice(Request $request, int $id, InvoicePdfService $pdfService)
    {
        $invoice = $request->user()->invoices()->findOrFail($id);

        Log::info('Customer downloaded invoice', ['invoice_id' => $invoice->id, 'user_id' => $request->user()->id]);

        // If PDF doesn't exist on disk (legacy or failed), try to generate it on the fly
        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            try {
                $path = $pdfService->generateAndStorePdf($invoice);
                $invoice->update(['pdf_path' => $path]);
            } catch (\Exception $e) {
                Log::error('Customer invoice download failed - PDF regeneration error', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
                return back()->with('error', 'Unable to download PDF. Please contact support.');
            }
        }

        return Storage::disk('public')->download(
            $invoice->pdf_path, 
            'Invoice-' . $invoice->invoice_number . '.pdf'
        );
    }
}
