<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    public const ENTERPRISE_STAGES = [
        'new',
        'contacted',
        'qualified',
        'site_visit_scheduled',
        'site_visit_completed',
        'negotiation',
        'won',
        'lost',
        'on_hold',
    ];

    public const ENTERPRISE_SOURCES = [
        'manual', 'csv', 'meta', 'website', 'walk_in', 'referral', 'other',
    ];

    protected $fillable = [
        'campaign_id',
        'enterprise_id',
        'team_id',
        'assigned_to',
        'assigned_manager_id',
        'name',
        'email',
        'phone',
        'company',
        'status',
        'enterprise_stage',
        'priority',
        'source',
        'meta_lead_id',
        'custom_fields',
        'inventory_unit_id',
        'inventory_project_id',
        'budget_min',
        'budget_max',
        'preferred_location',
        'preferred_configuration',
        'next_follow_up_at',
        'last_contacted_at',
        'assigned_at',
        'deal_value',
        'lost_reason',
        'won_at',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'deal_value' => 'decimal:2',
        'next_follow_up_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'assigned_at' => 'datetime',
        'won_at' => 'datetime',
    ];

    /**
     * Get the campaign this lead belongs to
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function inventoryUnit()
    {
        return $this->belongsTo(InventoryUnit::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedManager()
    {
        return $this->belongsTo(User::class, 'assigned_manager_id');
    }

    public function project()
    {
        return $this->belongsTo(InventoryProject::class, 'inventory_project_id');
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('created_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class);
    }

    public function scopeForEnterprise($query, int $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    public function scopeEnterpriseStage($query, string $stage)
    {
        return $query->where('enterprise_stage', $stage);
    }

    public function scopeVisibleTo($query, EnterpriseMembership $membership)
    {
        return app(\App\Services\EnterpriseHierarchyService::class)
            ->applyLeadScope($query, $membership);
    }

    public function isEnterpriseWon(): bool
    {
        return $this->enterprise_stage === 'won';
    }

    /**
     * Get notes for this lead
     */
    public function notes()
    {
        return $this->hasMany(LeadNote::class)->orderBy('created_at', 'desc');
    }

    /**
     * Meta Conversions API events.
     */
    public function metaCapiEvents()
    {
        return $this->hasMany(MetaCapiEvent::class)->orderByDesc('sent_at');
    }

    /**
     * Scope for leads by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for recent leads
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Leads that qualify as customers (won or linked to booked/sold unit).
     */
    public function scopeCustomers($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'won')
                ->orWhereHas('inventoryUnit', function ($unitQuery) {
                    $unitQuery->whereIn('availability_status', [
                        InventoryUnit::STATUS_BOOKED,
                        InventoryUnit::STATUS_SOLD,
                    ]);
                });
        });
    }

    /**
     * Scope leads to a realtor's campaigns.
     */
    public function scopeForRealtor($query, int $userId)
    {
        return $query->whereHas('campaign', fn ($q) => $q->where('user_id', $userId));
    }

    public function isCustomer(): bool
    {
        if ($this->status === 'won') {
            return true;
        }

        $unit = $this->relationLoaded('inventoryUnit')
            ? $this->inventoryUnit
            : $this->inventoryUnit()->first();

        return $unit && in_array($unit->availability_status, [
            InventoryUnit::STATUS_BOOKED,
            InventoryUnit::STATUS_SOLD,
        ], true);
    }

    /**
     * Add a note to this lead
     */
    public function addNote(string $note, int $userId): LeadNote
    {
        return $this->notes()->create([
            'user_id' => $userId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    /**
     * Get notes trail (chronological order)
     */
    public function getNotesTrail()
    {
        return $this->notes()->with('user')->get();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'blue',
            'contacted' => 'yellow',
            'qualified' => 'purple',
            'proposal' => 'indigo',
            'negotiation' => 'orange',
            'won' => 'green',
            'lost' => 'red',
            default => 'gray',
        };
    }
}
