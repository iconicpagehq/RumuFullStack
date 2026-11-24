<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'max_discount_amount' => $this->max_discount_amount,
            'min_purchase_amount' => $this->min_purchase_amount,
            'applicable_plans' => $this->applicable_plans,
            'start_date' => $this->start_date?->toIso8601String(),
            'expiration_date' => $this->expiration_date?->toIso8601String(),
            'usage_limit' => $this->usage_limit,
            'customer_usage_limit' => $this->customer_usage_limit,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'usages_count' => $this->whenCounted('usages'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
