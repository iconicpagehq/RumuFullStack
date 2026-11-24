<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\TaxSettingsService;
use App\Services\Payments\PaymentManager;
use App\Services\PromotionService;
use App\Services\SubscriptionActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends ApiController
{
    public function __construct(
        private PromotionService $promotions,
        private TaxSettingsService $taxSettings
    ) {}

    public function plans()
    {
        $plans = Plan::active()->ordered()->get();

        return $this->success([
            'plans' => PlanResource::collection($plans),
            'currency' => config('payments.currency', 'INR'),
            'tax' => $this->taxMeta(),
        ]);
    }

    public function summary(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration_months' => 'nullable|integer|min:1|max:36',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $plan = Plan::active()->find($request->integer('plan_id'));

        if (!$plan) {
            return $this->error('That plan is not available.', 422);
        }

        $quote = $this->buildQuote(
            $plan,
            $request->user(),
            $request->string('coupon_code')->toString() ?: null,
            max(1, (int) $request->input('duration_months', 1))
        );

        return $this->success($quote);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'code' => 'required|string|max:50',
            'duration_months' => 'nullable|integer|min:1|max:36',
        ]);

        $plan = Plan::active()->find($request->integer('plan_id'));

        if (!$plan) {
            return $this->error('That plan is not available.', 422);
        }

        $quote = $this->buildQuote(
            $plan,
            $request->user(),
            $request->string('code')->toString(),
            max(1, (int) $request->input('duration_months', 1))
        );

        if (!empty($quote['coupon_error'])) {
            return $this->error($quote['coupon_error'], 422);
        }

        return $this->success($quote, 'Coupon applied.');
    }

    public function removeCoupon(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration_months' => 'nullable|integer|min:1|max:36',
        ]);

        $plan = Plan::active()->find($request->integer('plan_id'));

        if (!$plan) {
            return $this->error('That plan is not available.', 422);
        }

        return $this->success($this->buildQuote(
            $plan,
            $request->user(),
            null,
            max(1, (int) $request->input('duration_months', 1))
        ));
    }

    public function createOrder(Request $request, PaymentManager $payments)
    {
        if ($request->user()->role !== 'realtor') {
            return $this->error('Only realtor accounts can purchase a plan.', 403);
        }

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration_months' => 'nullable|integer|min:1|max:36',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $plan = Plan::active()->find($validated['plan_id']);

        if (!$plan) {
            return $this->error('That plan is not available.', 422);
        }

        if ((float) $plan->price <= 0) {
            return $this->error('The free plan does not require a payment.', 422);
        }

        $duration = max(1, (int) ($validated['duration_months'] ?? 1));
        $quote = $this->buildQuote($plan, $request->user(), $validated['coupon_code'] ?? null, $duration);

        if (!empty($quote['coupon_error'])) {
            return $this->error($quote['coupon_error'], 422);
        }

        if (!$this->gatewayIsConfigured()) {
            return $this->error('Online payments are not configured yet.', 503);
        }

        DB::beginTransaction();

        try {
            $subscription = $request->user()->subscriptions()->create([
                'plan_name' => $plan->name,
                'duration_months' => $duration,
                'start_date' => now(),
                'status' => 'pending',
                'price' => $quote['price'],
                'gst' => $quote['tax_amount'],
                'discount' => $quote['discount'],
            ]);

            $coupon = $quote['coupon'] ? Coupon::find($quote['coupon']['id']) : null;

            $payment = $subscription->payments()->create([
                'amount' => $quote['total'],
                'currency' => $quote['currency'],
                'payment_status' => 'pending',
                'payment_method' => 'online',
                'coupon_id' => $coupon?->id,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Checkout could not create local records', ['error' => $e->getMessage()]);

            return $this->error('Unable to start checkout. Please try again.', 500);
        }

        try {
            $order = $payments->gateway()->createOrder(
                (float) $payment->amount,
                $payment->currency,
                "PAY_{$payment->id}",
                ['user_id' => $request->user()->id, 'subscription_id' => $subscription->id]
            );

            $payment->update([
                'gateway' => config('payments.default'),
                'gateway_order_id' => $order['gateway_order_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Checkout failed to create gateway order', ['error' => $e->getMessage()]);
            $payment->update(['payment_status' => 'failed']);

            return $this->error('Unable to initiate payment at this time.', 502);
        }

        return $this->success([
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'amount_in_subunits' => (int) round(((float) $payment->amount) * 100),
                'currency' => $payment->currency,
                'gateway' => $payment->gateway,
                'gateway_order_id' => $payment->gateway_order_id,
            ],
            'subscription_id' => $subscription->id,
            'razorpay_key' => config('payments.gateways.razorpay.key_id'),
            'prefill' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'contact' => $request->user()->phone,
            ],
            'quote' => $quote,
        ], 'Payment order created.', 201);
    }

    public function verify(Request $request, PaymentManager $payments, SubscriptionActivationService $activation)
    {
        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::where('gateway_order_id', $validated['razorpay_order_id'])
            ->with('subscription')
            ->first();

        if (!$payment || !$payment->subscription || $payment->subscription->user_id !== $request->user()->id) {
            return $this->error('Payment not found.', 404);
        }

        if ($payment->payment_status === 'completed') {
            return $this->success([
                'already_verified' => true,
                'invoice' => $this->invoiceForPayment($payment),
                'subscription' => new SubscriptionResource($payment->subscription),
            ], 'Payment already verified.');
        }

        $isValid = $payments->gateway()->verifyPayment([
            'gateway_order_id' => $validated['razorpay_order_id'],
            'gateway_payment_id' => $validated['razorpay_payment_id'],
            'gateway_signature' => $validated['razorpay_signature'],
        ]);

        if (!$isValid) {
            $payment->update(['payment_status' => 'failed']);

            Log::warning('API payment verification failed', ['payment_id' => $payment->id]);

            return $this->error('Payment verification failed.', 422);
        }

        $payment->update([
            'gateway_payment_id' => $validated['razorpay_payment_id'],
            'gateway_signature' => $validated['razorpay_signature'],
        ]);

        try {
            $activation->activateVerifiedSubscription($payment);
        } catch (\Throwable $e) {
            Log::error('Subscription activation failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            return $this->error('Payment verified but the subscription could not be activated. Please contact support.', 500);
        }

        return $this->success([
            'invoice' => $this->invoiceForPayment($payment->fresh()),
            'subscription' => new SubscriptionResource($payment->fresh()->subscription),
        ], 'Payment verified and plan activated.');
    }

    private function invoiceForPayment(Payment $payment): ?InvoiceResource
    {
        $invoice = Invoice::with('invoiceItems')->where('payment_id', $payment->id)->latest()->first();

        return $invoice ? new InvoiceResource($invoice) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuote(Plan $plan, $user, ?string $couponCode, int $duration): array
    {
        $basePrice = round((float) $plan->price * $duration, 2);
        $discount = 0.0;
        $couponPayload = null;
        $couponError = null;

        if ($couponCode) {
            $validation = $this->promotions->validateCoupon($couponCode, $user, $plan->name, $basePrice);

            if ($validation['valid']) {
                $discount = $this->promotions->calculateDiscount($validation['coupon'], $basePrice);
                $couponPayload = (new \App\Http\Resources\CouponResource($validation['coupon']))->resolve();
            } else {
                $couponError = $validation['message'];
            }
        }

        $taxable = max(0, $basePrice - $discount);
        $taxAmount = 0.0;
        $taxPercent = 0.0;
        $total = $taxable;

        if ($this->taxSettings->isEnabled()) {
            $taxPercent = $this->taxSettings->getDefaultGstPercent();
            $rate = $taxPercent / 100;

            if ($this->taxSettings->isInclusive()) {
                $taxAmount = round($taxable - ($taxable / (1 + $rate)), 2);
            } else {
                $taxAmount = round($taxable * $rate, 2);
                $total = round($taxable + $taxAmount, 2);
            }
        }

        return [
            'plan' => (new PlanResource($plan))->resolve(),
            'duration_months' => $duration,
            'price' => $basePrice,
            'discount' => $discount,
            'taxable_amount' => $taxable,
            'tax_percent' => $taxPercent,
            'tax_label' => $this->taxSettings->getTaxLabel(),
            'tax_inclusive' => $this->taxSettings->isInclusive(),
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => config('payments.currency', 'INR'),
            'coupon' => $couponPayload,
            'coupon_error' => $couponError,
        ];
    }

    private function gatewayIsConfigured(): bool
    {
        return config('payments.default') === 'razorpay'
            && !empty(config('payments.gateways.razorpay.key_id'))
            && !empty(config('payments.gateways.razorpay.key_secret'));
    }

    private function taxMeta(): array
    {
        return [
            'enabled' => $this->taxSettings->isEnabled(),
            'inclusive' => $this->taxSettings->isInclusive(),
            'percent' => $this->taxSettings->getDefaultGstPercent(),
            'label' => $this->taxSettings->getTaxLabel(),
        ];
    }
}
