<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryUnit extends Model
{
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_HOLD = 'hold';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_SOLD = 'sold';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_HOLD,
        self::STATUS_BOOKED,
        self::STATUS_SOLD,
    ];

    public const LAYOUTS = [
        'studio', '1_bhk', '2_bhk', '3_bhk', '4_bhk', '4+_bhk',
    ];

    protected $fillable = [
        'inventory_floor_id',
        'unit_number',
        'layout',
        'carpet_area',
        'built_up_area',
        'facing',
        'base_price',
        'availability_status',
        'layout_image',
        'lead_id',
        'notes',
    ];

    protected $casts = [
        'carpet_area' => 'decimal:2',
        'built_up_area' => 'decimal:2',
        'base_price' => 'decimal:2',
    ];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(InventoryFloor::class, 'inventory_floor_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(InventoryUnitStatusLog::class)->orderByDesc('created_at');
    }

    public function scopeAvailableForAssignment($query)
    {
        return $query->whereIn('availability_status', [self::STATUS_AVAILABLE, self::STATUS_HOLD]);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->whereHas('floor.tower.project', fn ($q) => $q->where('user_id', $userId));
    }

    public function scopeForEnterprise($query, int $enterpriseId)
    {
        return $query->whereHas('floor.tower.project', fn ($q) => $q->where('enterprise_id', $enterpriseId));
    }

    public function fullPath(): string
    {
        $floor = $this->floor;
        $tower = $floor?->tower;
        $project = $tower?->project;
        $wing = $floor?->wing;

        $parts = array_filter([
            $project?->name,
            $tower?->name,
            $wing?->name,
            $floor ? 'Floor ' . $floor->floor_number : null,
            'Unit ' . $this->unit_number,
        ]);

        return implode(' › ', $parts);
    }

    public function statusColor(): string
    {
        return match ($this->availability_status) {
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_HOLD => 'yellow',
            self::STATUS_BOOKED => 'blue',
            self::STATUS_SOLD => 'red',
            default => 'gray',
        };
    }
}
