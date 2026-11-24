<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'budget' => $this->budget,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'enterprise_id' => $this->enterprise_id,
            'team_id' => $this->team_id,
            'team' => $this->whenLoaded('team', fn () => new EnterpriseTeamResource($this->team)),
            'creator' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'leads_count' => $this->when(isset($this->leads_count), fn () => (int) $this->leads_count),
            'won_deals' => $this->when(isset($this->won_deals), fn () => (int) $this->won_deals),
            'deal_value' => $this->when(isset($this->deal_value), fn () => (float) $this->deal_value),
            'meta_lead_form_id' => $this->meta_lead_form_id,
            'meta_connected' => (bool) $this->meta_lead_form_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
