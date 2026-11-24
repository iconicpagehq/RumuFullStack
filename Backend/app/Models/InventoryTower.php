<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTower extends Model
{
    protected $fillable = [
        'inventory_project_id',
        'name',
        'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(InventoryProject::class, 'inventory_project_id');
    }

    public function wings(): HasMany
    {
        return $this->hasMany(InventoryWing::class)->orderBy('sort_order')->orderBy('name');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(InventoryFloor::class)->orderBy('floor_number');
    }
}
