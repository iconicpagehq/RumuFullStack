<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RealtorDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'avatar_url' => $this->avatar_url,
            'role' => $this->role,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'suspended_plan' => new PlanResource($this->whenLoaded('suspendedPlan')),
            'subscription_suspended' => $this->isSubscriptionSuspended(),
            'subscription_suspended_at' => $this->subscription_suspended_at?->toIso8601String(),
            'subscription_suspend_reason' => $this->subscription_suspend_reason,
            'campaigns_count' => $this->whenCounted('campaigns'),
            'leads_count' => $this->whenCounted('leads'),
            'campaigns' => CampaignResource::collection($this->whenLoaded('campaigns')),
            'subscriptions' => SubscriptionResource::collection($this->whenLoaded('subscriptions')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
