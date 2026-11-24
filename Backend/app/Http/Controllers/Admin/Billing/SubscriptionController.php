<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\InvoiceService;
use App\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of all subscriptions.
     */
    public function index()
    {
        $subscriptions = Subscription::with('user')->latest()->paginate(20);
        return view('admin.billing.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Display the dedicated customer billing page.
     */
    public function customerBilling(User $realtor)
    {
        $realtor->load(['subscriptions.payments', 'payments']);
        $plans = config('billing.plans');
        
        return view('admin.billing.customer', compact('realtor', 'plans'));
    }

    /**
     * Assign a new plan (create subscription, payment, and invoice).
     */
    public function storeCustomerPlan(StoreSubscriptionRequest $request, User $realtor, InvoiceService $invoiceService)
    {
        try {
            DB::beginTransaction();

            // Create Subscription (preserve history by creating a new one)
            $subscription = $realtor->subscriptions()->create([
                'plan_name' => $request->plan_name,
                'duration_months' => $request->duration_months,
                'start_date' => $request->start_date,
                'expiry_date' => $request->expiry_date,
                'price' => $request->price,
                'gst' => $request->gst ?? 0,
                'discount' => $request->discount ?? 0,
                'status' => $request->subscription_status,
                'internal_notes' => $request->internal_notes,
            ]);

            // Calculate total amount (Price + GST - Discount)
            $totalAmount = $request->price + ($request->gst ?? 0) - ($request->discount ?? 0);

            // Create Payment
            $payment = $subscription->payments()->create([
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'amount' => $totalAmount,
                'currency' => $request->currency,
                'payment_status' => $request->payment_status,
                'payment_date' => now(),
            ]);

            // Generate Invoice
            $invoice = $invoiceService->generateInvoice($subscription, $payment, [
                'price' => $request->price,
                'gst' => $request->gst,
                'discount' => $request->discount,
            ]);

            DB::commit();

            Log::info('Plan assigned and invoice generated successfully', [
                'admin_id' => auth()->id(),
                'realtor_id' => $realtor->id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
            ]);

            return redirect()->route('admin.realtors.billing', $realtor)
                ->with('success', 'Plan assigned and payment recorded successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign plan', [
                'admin_id' => auth()->id(),
                'realtor_id' => $realtor->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withInput()->with('error', 'Failed to assign plan. Please try again.');
        }
    }

    /**
     * Administratively extend an active subscription.
     */
    public function extend(Request $request, Subscription $subscription, SubscriptionService $service)
    {
        $request->validate([
            'months_to_add' => 'required|integer|min:1|max:60'
        ]);

        $service->extend($subscription, $request->months_to_add);

        Log::info('Subscription extended', [
            'admin_id' => auth()->id(),
            'subscription_id' => $subscription->id,
            'months_added' => $request->months_to_add
        ]);

        return redirect()->back()->with('success', "Subscription extended by {$request->months_to_add} months.");
    }

    /**
     * Cancel an active subscription.
     */
    public function cancel(Subscription $subscription, SubscriptionService $service)
    {
        $service->cancel($subscription);

        Log::info('Subscription cancelled', [
            'admin_id' => auth()->id(),
            'subscription_id' => $subscription->id
        ]);

        return redirect()->back()->with('success', 'Subscription has been cancelled successfully.');
    }
}
