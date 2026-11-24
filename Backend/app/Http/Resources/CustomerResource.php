<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'campaign' => $this->whenLoaded('campaign', fn () => [
                'id' => $this->campaign->id,
                'name' => $this->campaign->name,
            ]),
            'inventory_unit' => $this->whenLoaded('inventoryUnit', fn () => [
                'id' => $this->inventoryUnit->id,
                'full_path' => $this->inventoryUnit->fullPath(),
                'availability_status' => $this->inventoryUnit->availability_status,
            ]),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
