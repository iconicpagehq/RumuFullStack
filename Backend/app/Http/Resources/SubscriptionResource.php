<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total = (float) $this->price + (float) $this->gst - (float) $this->discount;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'plan_name' => $this->plan_name,
            'duration_months' => $this->duration_months,
            'start_date' => $this->start_date?->toIso8601String(),
            'expiry_date' => $this->expiry_date?->toIso8601String(),
            'status' => $this->status,
            'price' => $this->price,
            'gst' => $this->gst,
            'discount' => $this->discount,
            'total' => round($total, 2),
            'internal_notes' => $this->internal_notes,
            'user' => new UserResource($this->whenLoaded('user')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
