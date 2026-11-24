<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'priority' => $this->priority,
            'deal_value' => $this->deal_value,
            'next_follow_up_at' => $this->next_follow_up_at?->toIso8601String(),
            'source' => $this->source,
            'custom_fields' => $this->custom_fields,
            'inventory_unit_id' => $this->inventory_unit_id,
            'is_customer' => $this->isCustomer(),
            'tenant' => $this->enterprise_id ? 'enterprise' : 'realtor',
            'enterprise' => $this->whenLoaded('enterprise', fn () => $this->enterprise ? [
                'id' => $this->enterprise->id,
                'name' => $this->enterprise->name,
            ] : null),
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'inventory_unit' => new InventoryUnitResource($this->whenLoaded('inventoryUnit')),
            'notes' => LeadNoteResource::collection($this->whenLoaded('notes')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
