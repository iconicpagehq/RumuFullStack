<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'subscription_id' => $this->subscription_id,
            'payment_id' => $this->payment_id,
            'invoice_id' => $this->invoice_id,
            'channel' => $this->channel,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'read_at' => $this->read_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'is_read' => $this->read_at !== null,
            'user' => new UserResource($this->whenLoaded('user')),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
