<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'payment_date' => $this->payment_date?->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'gateway' => $this->gateway,
            'gateway_order_id' => $this->gateway_order_id,
            'gateway_payment_id' => $this->gateway_payment_id,
            'gateway_signature' => $this->gateway_signature,
            'coupon_id' => $this->coupon_id,
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
