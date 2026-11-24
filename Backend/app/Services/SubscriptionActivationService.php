<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Services\Billing\TaxSettingsService;

class SubscriptionActivationService
{
    protected InvoiceService $invoiceService;
    protected PromotionService $promotionService;
    protected TaxSettingsService $taxSettings;

    public function __construct(
        InvoiceService $invoiceService, 
        PromotionService $promotionService, 
        TaxSettingsService $taxSettings
    ) {
        $this->invoiceService = $invoiceService;
        $this->promotionService = $promotionService;
        $this->taxSettings = $taxSettings;
    }

    /**
     * Complete a payment and activate its associated subscription securely.
     * Uses database transactions to ensure data integrity.
     */
    public function activateVerifiedSubscription(Payment $payment, array $webhookPayload = []): void
    {
        // Idempotency check: if already completed, ignore
        if ($payment->payment_status === 'completed' || $payment->verified_at !== null) {
            Log::info('Payment already verified. Ignoring duplicate activation.', [
                'payment_id' => $payment->id,
                'gateway_payment_id' => $payment->gateway_payment_id
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // 1. Update Payment
            $payment->update([
                'payment_status' => 'completed',
                'verified_at' => now(),
                'payment_date' => now(),
                'webhook_payload' => !empty($webhookPayload) ? json_encode($webhookPayload) : $payment->webhook_payload,
            ]);

            $subscription = $payment->subscription;
            
            // 2. Calculate dates (handle renewal overlap)
            $user = $subscription->user;
            $currentActive = $user->subscriptions()
                ->where('id', '!=', $subscription->id)
                ->where('status', 'active')
                ->where('expiry_date', '>', now())
                ->latest('expiry_date')
                ->first();

            $startDate = $currentActive ? (clone $currentActive->expiry_date) : now();
            $expiryDate = (clone $startDate)->addMonths($subscription->duration_months);

            // 3. Activate Subscription
            $subscription->update([
                'status' => 'active',
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'activated_at' => now()
            ]);

            // Update user's plan_id
            $plan = \App\Models\Plan::where('name', $subscription->plan_name)->first();
            if ($plan) {
                $user->update(['plan_id' => $plan->id]);
            }

            // 4. Handle Coupon Application
            $discountAmount = 0;
            if ($payment->coupon_id) {
                // Determine original price from subscription
                $originalPrice = $subscription->price;
                $coupon = $payment->coupon;
                if ($coupon) {
                    $discountAmount = $this->promotionService->calculateDiscount($coupon, $originalPrice);
                    $this->promotionService->applyCoupon($coupon, $payment, $subscription->user, $discountAmount);
                }
            }

            // 5. Generate Invoice (which queues PDF & Email)
            $gst = 0;
            $subtotal = $subscription->price;
            $discountedPrice = max(0, $subtotal - $discountAmount);
            
            if ($this->taxSettings->isEnabled()) {
                $percent = $this->taxSettings->getDefaultGstPercent();
                if ($this->taxSettings->isInclusive()) {
                    // tax = price - (price / (1 + rate))
                    $gst = $discountedPrice - ($discountedPrice / (1 + ($percent / 100)));
                    $subtotal = $discountedPrice - $gst + $discountAmount; // Adjust subtotal for inclusive tax
                } else {
                    $gst = $discountedPrice * ($percent / 100);
                }
            }

            $billingData = [
                'price' => $subtotal,
                'gst' => $gst,
                'discount' => $discountAmount,
            ];

            $this->invoiceService->generateInvoice($subscription, $payment, $billingData);

            DB::commit();

            Log::info('Subscription activated successfully after verified payment.', [
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to activate verified subscription.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
