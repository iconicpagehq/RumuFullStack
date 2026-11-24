<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryProjectResource extends JsonResource
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
            'towers_count' => $this->when(isset($this->towers_count), $this->towers_count),
            'summary' => $this->when(isset($this->summary), $this->summary),
            'towers' => $this->whenLoaded('towers', function () {
                return $this->towers->map(fn ($tower) => [
                    'id' => $tower->id,
                    'name' => $tower->name,
                    'sort_order' => $tower->sort_order,
                    'wings' => $tower->relationLoaded('wings')
                        ? $tower->wings->map(fn ($wing) => [
                            'id' => $wing->id,
                            'name' => $wing->name,
                            'sort_order' => $wing->sort_order,
                        ])
                        : [],
                    'floors' => $tower->relationLoaded('floors')
                        ? $tower->floors->map(fn ($floor) => [
                            'id' => $floor->id,
                            'floor_number' => $floor->floor_number,
                            'label' => $floor->displayLabel(),
                            'inventory_wing_id' => $floor->inventory_wing_id,
                            'wing_name' => $floor->wing?->name,
                            'layout_image' => $floor->layout_image,
                            'units_count' => $floor->relationLoaded('units') ? $floor->units->count() : null,
                            'units' => $floor->relationLoaded('units')
                                ? $floor->units->map(fn ($unit) => [
                                    'id' => $unit->id,
                                    'unit_number' => $unit->unit_number,
                                    'layout' => $unit->layout,
                                    'carpet_area' => $unit->carpet_area,
                                    'built_up_area' => $unit->built_up_area,
                                    'facing' => $unit->facing,
                                    'base_price' => $unit->base_price,
                                    'availability_status' => $unit->availability_status,
                                    'layout_image' => $unit->layout_image,
                                    'floor_id' => $unit->inventory_floor_id,
                                    'full_path' => $unit->fullPath(),
                                ])
                                : [],
                        ])
                        : [],
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
