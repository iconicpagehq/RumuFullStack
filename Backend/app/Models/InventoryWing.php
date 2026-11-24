<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryWing extends Model
{
    protected $fillable = [
        'inventory_tower_id',
        'name',
        'sort_order',
    ];

    public function tower(): BelongsTo
    {
        return $this->belongsTo(InventoryTower::class, 'inventory_tower_id');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(InventoryFloor::class)->orderBy('floor_number');
    }
}
