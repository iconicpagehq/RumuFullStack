<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'location' => $this->location,
            'city' => $this->city,
            'description' => $this->description,
            'status' => $this->status,
            'enterprise_id' => $this->enterprise_id,
            'towers' => $this->whenLoaded('towers', fn () => $this->towers->map(fn ($tower) => [
                'id' => $tower->id,
                'name' => $tower->name,
                'floors' => $tower->relationLoaded('floors') ? $tower->floors->map(fn ($floor) => [
                    'id' => $floor->id,
                    'floor_number' => $floor->floor_number,
                    'units' => $floor->relationLoaded('units') ? InventoryUnitResource::collection($floor->units) : [],
                ]) : [],
            ])),
            'availability' => $this->when(isset($this->availability), fn () => $this->availability),
            'units_count' => $this->when(isset($this->units_count), fn () => (int) $this->units_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
