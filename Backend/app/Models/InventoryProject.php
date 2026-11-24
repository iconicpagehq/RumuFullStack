<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryProject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'enterprise_id',
        'name',
        'slug',
        'location',
        'city',
        'description',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryProject $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function scopeForEnterprise($query, int $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    public function towers(): HasMany
    {
        return $this->hasMany(InventoryTower::class)->orderBy('sort_order')->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getUnitsQuery()
    {
        return InventoryUnit::query()
            ->whereHas('floor.tower', fn ($q) => $q->where('inventory_project_id', $this->id));
    }

    public function availabilitySummary(): array
    {
        $counts = $this->getUnitsQuery()
            ->selectRaw('availability_status, COUNT(*) as total')
            ->groupBy('availability_status')
            ->pluck('total', 'availability_status');

        $total = $this->getUnitsQuery()->count();

        return [
            'available' => (int) ($counts['available'] ?? 0),
            'hold' => (int) ($counts['hold'] ?? 0),
            'booked' => (int) ($counts['booked'] ?? 0),
            'sold' => (int) ($counts['sold'] ?? 0),
            'total' => $total,
        ];
    }
}
