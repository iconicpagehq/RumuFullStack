<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'user_id' => $this->user_id,
            'subscription_id' => $this->subscription_id,
            'payment_id' => $this->payment_id,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'status' => $this->status,
            'pdf_path' => $this->pdf_path,
            'has_pdf' => (bool) $this->pdf_path,
            'emailed_at' => $this->emailed_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'items' => $this->whenLoaded('invoiceItems', fn () => $this->invoiceItems->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
