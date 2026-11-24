<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'key',
        'name',
        'audience',
        'max_campaigns',
        'max_leads_per_campaign',
        'max_managers',
        'max_employees',
        'max_teams',
        'max_monthly_leads',
        'max_inventory_units',
        'advanced_reports',
        'meta_integrations',
        'is_default',
        'is_active',
        'price',
        'sort_order',
        'header_color',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'advanced_reports' => 'boolean',
        'meta_integrations' => 'boolean',
        'price' => 'decimal:2',
        'max_managers' => 'integer',
        'max_employees' => 'integer',
        'max_teams' => 'integer',
        'max_monthly_leads' => 'integer',
        'max_inventory_units' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEnterprise($query)
    {
        return $query->whereIn('audience', ['enterprise', 'both']);
    }

    public function supportsEnterprise(): bool
    {
        return in_array($this->audience, ['enterprise', 'both'], true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Get users with this plan
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this is the Basic plan
     */
    public function isBasic(): bool
    {
        return $this->key === 'basic';
    }

    /**
     * Check if this is the Pro plan
     */
    public function isPro(): bool
    {
        return $this->key === 'pro';
    }

    /**
     * Whether a Realtor can request an upgrade to this plan from their current plan.
     */
    public function isUpgradeFrom(?Plan $current): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($current && $current->id === $this->id) {
            return false;
        }

        if (!$current) {
            return true;
        }

        return ($this->sort_order ?? 0) > ($current->sort_order ?? 0);
    }

    public function resolvedHeaderColor(): string
    {
        $color = $this->header_color ?: '#6b7280';

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#6b7280';
    }

    public function headerBackgroundStyle(): string
    {
        $base = $this->resolvedHeaderColor();
        $dark = $this->darkenHexColor($base);

        return "background: linear-gradient(to right, {$base}, {$dark});";
    }

    public function darkenHexColor(string $hex, float $amount = 0.15): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#4b5563';
        }

        $r = max(0, min(255, (int) round(hexdec(substr($hex, 0, 2)) * (1 - $amount))));
        $g = max(0, min(255, (int) round(hexdec(substr($hex, 2, 2)) * (1 - $amount))));
        $b = max(0, min(255, (int) round(hexdec(substr($hex, 4, 2)) * (1 - $amount))));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Boot the model to set default key if not provided
     */
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($plan) {
            if (empty($plan->key) && !empty($plan->name)) {
                $plan->key = \Illuminate\Support\Str::slug($plan->name);
            }

            if ($plan->sort_order === null || $plan->sort_order === 0) {
                $maxOrder = static::max('sort_order') ?? 0;
                $plan->sort_order = $maxOrder + 1;
            }

            if (empty($plan->header_color)) {
                $plan->header_color = '#6b7280';
            }
        });
    }

    /**
     * Check if user can create a new campaign
     * Uses database locking to prevent race conditions
     */
    public function canCreateCampaign(User $user): bool
    {
        if ($this->max_campaigns === null) {
            return true; // Unlimited
        }

        // Use pessimistic locking to prevent race conditions
        return \DB::transaction(function () use ($user) {
            // Lock the user record to prevent concurrent modifications
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            
            $currentCount = $lockedUser->campaigns()->count();
            
            return $currentCount < $this->max_campaigns;
        });
    }

    /**
     * Check if user can add lead to campaign
     * Uses database locking to prevent race conditions
     */
    public function canAddLead($campaign): bool
    {
        if ($this->max_leads_per_campaign === null) {
            return true; // Unlimited
        }

        // Use pessimistic locking to prevent race conditions
        return \DB::transaction(function () use ($campaign) {
            // Lock the campaign record to prevent concurrent modifications
            $lockedCampaign = Campaign::where('id', $campaign->id)->lockForUpdate()->first();
            
            $currentCount = $lockedCampaign->leads()->count();
            
            return $currentCount < $this->max_leads_per_campaign;
        });
    }
}
