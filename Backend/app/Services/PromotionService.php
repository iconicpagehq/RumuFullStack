<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    /**
     * Validate if a coupon can be applied.
     * Returns an array: ['valid' => bool, 'message' => string]
     */
    public function validateCoupon(string $code, User $user, string $planName, float $amount): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code.'];
        }

        if (!$coupon->is_active) {
            return ['valid' => false, 'message' => 'This coupon is no longer active.'];
        }

        if ($coupon->start_date && $coupon->start_date > now()) {
            return ['valid' => false, 'message' => 'This coupon is not active yet.'];
        }

        if ($coupon->expiration_date && $coupon->expiration_date < now()) {
            return ['valid' => false, 'message' => 'This coupon has expired.'];
        }

        if ($coupon->min_purchase_amount > 0 && $amount < $coupon->min_purchase_amount) {
            return ['valid' => false, 'message' => 'Minimum purchase amount not reached.'];
        }

        if (!empty($coupon->applicable_plans)) {
            $plans = array_map('strtolower', $coupon->applicable_plans);
            if (!in_array(strtolower($planName), $plans)) {
                return ['valid' => false, 'message' => 'Coupon is not applicable for this plan.'];
            }
        }

        if ($coupon->usage_limit !== null) {
            $totalUsages = $coupon->usages()->count();
            if ($totalUsages >= $coupon->usage_limit) {
                return ['valid' => false, 'message' => 'Coupon usage limit reached.'];
            }
        }

        if ($coupon->customer_usage_limit !== null) {
            $userUsages = $coupon->usages()->where('user_id', $user->id)->count();
            if ($userUsages >= $coupon->customer_usage_limit) {
                return ['valid' => false, 'message' => 'You have already reached the usage limit for this coupon.'];
            }
        }

        return ['valid' => true, 'message' => 'Coupon is valid.', 'coupon' => $coupon];
    }

    /**
     * Calculate exact discount amount based on coupon rules.
     */
    public function calculateDiscount(Coupon $coupon, float $originalAmount): float
    {
        $discountAmount = 0.0;

        if ($coupon->discount_type === 'fixed') {
            $discountAmount = min($coupon->discount_value, $originalAmount); // Cannot discount more than total
        } elseif ($coupon->discount_type === 'percentage') {
            $discountAmount = ($originalAmount * $coupon->discount_value) / 100;
        }

        if ($coupon->max_discount_amount !== null && $discountAmount > $coupon->max_discount_amount) {
            $discountAmount = $coupon->max_discount_amount;
        }

        return round($discountAmount, 2);
    }

    /**
     * Apply the coupon to a completed payment.
     */
    public function applyCoupon(Coupon $coupon, Payment $payment, User $user, float $discountAmount): CouponUsage
    {
        $payment->update(['coupon_id' => $coupon->id]);

        $usage = CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'discount_amount' => $discountAmount,
        ]);

        Log::info('Coupon applied to payment.', [
            'coupon_id' => $coupon->id,
            'payment_id' => $payment->id,
            'discount' => $discountAmount
        ]);

        return $usage;
    }
}
