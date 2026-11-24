<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enterprise_id' => $this->enterprise_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'lead_distribution_mode' => $this->lead_distribution_mode,
            'settings' => $this->settings ?? [],
            'manager' => $this->whenLoaded('manager', fn () => new UserResource($this->manager)),
            'members' => $this->whenLoaded('members', fn () => UserResource::collection($this->members)),
            'members_count' => $this->whenCounted('memberships'),
            'open_leads_count' => $this->when(isset($this->open_leads_count), fn () => (int) $this->open_leads_count),
            'won_deals_count' => $this->when(isset($this->won_deals_count), fn () => (int) $this->won_deals_count),
            'deal_value' => $this->when(isset($this->deal_value), fn () => (float) $this->deal_value),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
