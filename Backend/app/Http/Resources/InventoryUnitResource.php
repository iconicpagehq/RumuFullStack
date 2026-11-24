<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_number' => $this->unit_number,
            'layout' => $this->layout,
            'carpet_area' => $this->carpet_area,
            'built_up_area' => $this->built_up_area,
            'facing' => $this->facing,
            'base_price' => $this->base_price,
            'availability_status' => $this->availability_status,
            'full_path' => $this->fullPath(),
            'floor_id' => $this->inventory_floor_id,
            'label' => $this->fullPath(),
            'lead_id' => $this->lead_id,
            'lead' => $this->lead ? [
                'id' => $this->lead->id,
                'name' => $this->lead->name,
                'phone' => $this->lead->phone,
                'email' => $this->lead->email,
                'status' => $this->lead->status,
            ] : null,
        ];
    }
}
