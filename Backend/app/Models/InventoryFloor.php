<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryFloor extends Model
{
    protected $fillable = [
        'inventory_tower_id',
        'inventory_wing_id',
        'floor_number',
        'layout_image',
    ];

    public function tower(): BelongsTo
    {
        return $this->belongsTo(InventoryTower::class, 'inventory_tower_id');
    }

    public function wing(): BelongsTo
    {
        return $this->belongsTo(InventoryWing::class, 'inventory_wing_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(InventoryUnit::class)->orderBy('unit_number');
    }

    public function displayLabel(): string
    {
        $towerName = $this->tower?->name ?? '';
        $lower = strtolower($towerName);

        if (str_contains($lower, 'plot') || str_contains($lower, 'land') || str_contains($lower, 'phase') || str_contains($lower, 'sector')) {
            $label = 'Phase ' . $this->floor_number;
        } elseif (
            str_contains($lower, 'row') ||
            str_contains($lower, 'villa') ||
            str_contains($lower, 'house') ||
            str_contains($lower, 'bungalow') ||
            str_contains($lower, 'townhouse') ||
            str_contains($lower, 'duplex')
        ) {
            if ($this->floor_number === 0) {
                $label = 'Ground Level (G)';
            } elseif ($this->floor_number === 1) {
                $label = 'First Floor (Level 1)';
            } elseif ($this->floor_number === 2) {
                $label = 'Second Floor (Level 2)';
            } elseif ($this->floor_number === 3) {
                $label = 'Third Floor (Level 3)';
            } elseif ($this->floor_number === -1) {
                $label = 'Basement';
            } else {
                $label = 'Level ' . $this->floor_number;
            }
        } else {
            if ($this->floor_number === 0) {
                $label = 'Ground Floor';
            } elseif ($this->floor_number < 0) {
                $label = 'Basement ' . abs($this->floor_number);
            } else {
                $label = 'Floor ' . $this->floor_number;
            }
        }

        if ($this->wing) {
            $label = $this->wing->name . ' · ' . $label;
        }

        return $label;
    }
}
