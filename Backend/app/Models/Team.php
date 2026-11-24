<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'name',
        'description',
        'manager_id',
        'status',
        'lead_distribution_mode',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function memberships()
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_memberships')
            ->withPivot(['is_primary', 'last_assigned_at', 'joined_at'])
            ->withTimestamps();
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class);
    }

    public function salesTargets()
    {
        return $this->hasMany(SalesTarget::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
