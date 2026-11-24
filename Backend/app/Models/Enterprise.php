<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Enterprise extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'slug',
        'email',
        'phone',
        'address',
        'website',
        'business_type',
        'company_size',
        'logo_path',
        'rera_number',
        'working_hours',
        'extra_managers',
        'extra_employees',
        'owner_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'settings',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'settings' => 'array',
        'working_hours' => 'array',
        'extra_managers' => 'integer',
        'extra_employees' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Enterprise $enterprise) {
            if (empty($enterprise->slug)) {
                $enterprise->slug = static::uniqueSlug($enterprise->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'enterprise';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function memberships()
    {
        return $this->hasMany(EnterpriseMembership::class);
    }

    public function admins()
    {
        return $this->memberships()->where('role', 'admin');
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function invitations()
    {
        return $this->hasMany(EnterpriseInvitation::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function projects()
    {
        return $this->hasMany(InventoryProject::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
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

    public function auditLogs()
    {
        return $this->hasMany(EnterpriseAuditLog::class);
    }

    public function seatRequests()
    {
        return $this->hasMany(EnterpriseSeatRequest::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }
}
