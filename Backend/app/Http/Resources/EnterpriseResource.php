<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'website' => $this->website,
            'business_type' => $this->business_type,
            'company_size' => $this->company_size,
            'logo_url' => $this->logoUrl(),
            'rera_number' => $this->rera_number,
            'working_hours' => $this->working_hours ?? [],
            'extra_managers' => (int) ($this->extra_managers ?? 0),
            'extra_employees' => (int) ($this->extra_employees ?? 0),
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'is_on_trial' => $this->isOnTrial(),
            'settings' => $this->settings ?? [],
            'plan' => $this->whenLoaded('plan', fn () => new PlanResource($this->plan)),
            'owner' => $this->whenLoaded('owner', fn () => new UserResource($this->owner)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
