<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnterpriseMembership extends Model
{
    use HasFactory;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EMPLOYEE = 'employee';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REMOVED = 'removed';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_EMPLOYEE,
    ];

    protected $fillable = [
        'enterprise_id',
        'user_id',
        'role',
        'status',
        'designation',
        'employee_code',
        'joined_at',
        'suspended_at',
        'removed_at',
        'invited_by',
        'reports_to_membership_id',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'suspended_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function teamMemberships()
    {
        return $this->hasMany(TeamMembership::class, 'user_id', 'user_id');
    }

    public function primaryTeamMembership()
    {
        return $this->hasOne(TeamMembership::class, 'user_id', 'user_id')->where('is_primary', true);
    }

    public function reportsTo()
    {
        return $this->belongsTo(self::class, 'reports_to_membership_id');
    }

    public function directReports()
    {
        return $this->hasMany(self::class, 'reports_to_membership_id');
    }

    public function activeDirectReports()
    {
        return $this->directReports()->where('status', '!=', self::STATUS_REMOVED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeNotRemoved($query)
    {
        return $query->where('status', '!=', self::STATUS_REMOVED);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRemoved(): bool
    {
        return $this->status === self::STATUS_REMOVED;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    /**
     * A lead employee is not a new role: it is any employee with direct reports.
     */
    public function isLead(): bool
    {
        if (array_key_exists('direct_reports_count', $this->attributes)) {
            return (int) $this->attributes['direct_reports_count'] > 0;
        }

        return $this->activeDirectReports()->exists();
    }
}
