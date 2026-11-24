<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanUpgradeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'current_plan_id',
        'requested_plan_id',
        'status',
        'admin_notes',
        'approved_at',
        'approved_by',
        'expires_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who requested the upgrade
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the current plan
     */
    public function currentPlan()
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    /**
     * Get the requested plan
     */
    public function requestedPlan()
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }

    /**
     * Get the admin who approved/rejected
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function displayExpiresAt(): ?\Carbon\Carbon
    {
        if ($this->expires_at) {
            return $this->expires_at->copy();
        }

        if ($this->status === 'approved' && $this->approved_at && $this->requestedPlan?->isPro()) {
            return $this->approved_at->copy()->addDays(30);
        }

        return null;
    }

    public function isPlanExpired(): bool
    {
        $expiresAt = $this->displayExpiresAt();

        return $expiresAt !== null && $expiresAt->isPast();
    }

    public function expiryDaysRemaining(): ?int
    {
        $expiresAt = $this->displayExpiresAt();
        if (!$expiresAt || $expiresAt->isPast()) {
            return null;
        }

        return max(0, (int) now()->diffInDays($expiresAt));
    }
}
