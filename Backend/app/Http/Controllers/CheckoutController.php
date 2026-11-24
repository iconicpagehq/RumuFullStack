<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use App\Services\SubscriptionActivationService;
use App\Services\PromotionService;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Show the checkout page and create the gateway order.
     */
    public function show(Request $request, PaymentManager $paymentManager, PromotionService $promotionService, \App\Services\Billing\TaxSettingsService $taxSettings)
    {
        return redirect()->back()->with('error', 'We have not started with the Online payment our developers are working on it');

        $plan = \App\Models\Plan::where('key', $request->input('plan', 'pro'))->first();
        if (!$plan) {
            $plan = \App\Models\Plan::firstOrFail();
        }

        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Create a pending subscription
        $subscription = $user->subscriptions()->create([
            'plan_name' => $plan->name,
            'duration_months' => 1, // Defaulting to monthly for checkout flow
            'start_date' => now(), // Will be updated on activation
            'status' => 'pending',
            'price' => $plan->price
        ]);

        $finalAmount = $plan->price;
        $discountAmount = 0;
        $coupon = null;
        $validationMessage = null;

        // Apply Coupon if in session
        if (session()->has('checkout_coupon')) {
            $couponCode = session()->get('checkout_coupon');
            $validation = $promotionService->validateCoupon($couponCode, $user, $plan->name, $plan->price);
            
            if ($validation['valid']) {
                $coupon = $validation['coupon'];
                $discountAmount = $promotionService->calculateDiscount($coupon, $plan->price);
                $finalAmount = max(0, $plan->price - $discountAmount);
            } else {
                $validationMessage = $validation['message'];
                session()->forget('checkout_coupon');
            }
        }

        $gstAmount = 0;
        $gstPercent = 0;
        if ($taxSettings->isEnabled() && !$taxSettings->isInclusive()) {
            $gstPercent = $taxSettings->getDefaultGstPercent();
            $gstAmount = $finalAmount * ($gstPercent / 100);
            $finalAmount += $gstAmount;
        }

        // 2. Create a pending payment record
        $payment = $subscription->payments()->create([
            'amount' => $finalAmount,
            'currency' => config('payments.currency', 'INR'),
            'payment_status' => 'pending',
            'payment_method' => 'online', // placeholder until gateway confirms
            'coupon_id' => $coupon ? $coupon->id : null,
        ]);

        // 3. Create Gateway Order
        try {
            $gateway = $paymentManager->gateway();
            $order = $gateway->createOrder(
                $payment->amount, 
                $payment->currency, 
                "PAY_{$payment->id}",
                ['user_id' => $user->id, 'subscription_id' => $subscription->id]
            );

            $payment->update([
                'gateway' => config('payments.default'),
                'gateway_order_id' => $order['gateway_order_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Checkout failed to create gateway order', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Unable to initiate payment at this time.');
        }

        Log::info('Checkout session created', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'gateway_order_id' => $payment->gateway_order_id,
            'coupon_id' => $payment->coupon_id
        ]);

        return view('billing.checkout', compact('plan', 'payment', 'user', 'coupon', 'discountAmount', 'validationMessage', 'gstAmount', 'gstPercent'));
    }

    /**
     * Apply coupon to session.
     */
    public function applyCoupon(Request $request, PromotionService $promotionService)
    {
        $request->validate(['code' => 'required|string']);
        $plan = \App\Models\Plan::where('key', $request->input('plan', 'pro'))->first() ?? \App\Models\Plan::firstOrFail();

        $validation = $promotionService->validateCoupon($request->code, auth()->user(), $plan->name, $plan->price);
        
        if ($validation['valid']) {
            session()->put('checkout_coupon', $request->code);
            return redirect()->back()->with('success', 'Coupon applied successfully.');
        }

        return redirect()->back()->with('error', $validation['message']);
    }

    /**
     * Remove coupon from session.
     */
    public function removeCoupon()
    {
        session()->forget('checkout_coupon');
        return redirect()->back()->with('success', 'Coupon removed.');
    }

    /**
     * Synchronous redirect from gateway (verify payment).
     */
    public function verify(Request $request, PaymentManager $paymentManager, SubscriptionActivationService $activationService)
    {
        $payment = Payment::where('gateway_order_id', $request->razorpay_order_id)->firstOrFail();

        // Already verified by webhook or previous request?
        if ($payment->payment_status === 'completed') {
            return redirect()->route('checkout.success');
        }

        $gateway = $paymentManager->gateway();
        
        $isValid = $gateway->verifyPayment([
            'gateway_order_id' => $request->razorpay_order_id,
            'gateway_payment_id' => $request->razorpay_payment_id,
            'gateway_signature' => $request->razorpay_signature,
        ]);

        if ($isValid) {
            $payment->update([
                'gateway_payment_id' => $request->razorpay_payment_id,
                'gateway_signature' => $request->razorpay_signature,
            ]);

            $activationService->activateVerifiedSubscription($payment);

            return redirect()->route('checkout.success');
        }

        // Verification failed
        $payment->update(['payment_status' => 'failed']);
        
        Log::warning('Synchronous payment verification failed', [
            'payment_id' => $payment->id,
            'request' => $request->all()
        ]);

        return redirect()->route('checkout.failure');
    }

    public function success()
    {
        return view('billing.success');
    }

    public function failure()
    {
        return view('billing.failure');
    }
}
