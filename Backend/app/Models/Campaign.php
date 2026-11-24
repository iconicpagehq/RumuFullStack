<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'enterprise_id',
        'team_id',
        'name',
        'description',
        'status',
        'budget',
        'start_date',
        'end_date',
        'meta_lead_form_id',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the user that owns the campaign
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get leads for this campaign
     */
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeForEnterprise($query, int $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    /**
     * Scope for active campaigns
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for campaigns by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get leads count
     */
    public function leadsCount(): int
    {
        return $this->leads()->count();
    }

    /**
     * Check if can add more leads
     */
    public function canAddLead(): bool
    {
        $plan = $this->user->plan;

        if (!$plan) {
            return false;
        }

        return $plan->canAddLead($this);
    }

    /**
     * Get leads by status
     */
    public function getLeadsByStatus(string $status)
    {
        return $this->leads()->where('status', $status)->get();
    }

    /**
     * Get Meta CAPI Settings for this campaign
     */
    public function metaCapiSettings()
    {
        return $this->hasOne(MetaCapiSetting::class);
    }
}
