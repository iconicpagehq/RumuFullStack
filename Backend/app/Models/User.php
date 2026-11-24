<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'google_avatar',
        'facebook_id',
        'facebook_avatar',
        'facebook_page_id',
        'facebook_page_access_token',
        'phone',
        'company',
        'bio',
        'avatar',
        'email_notifications',
        'marketing_emails',
        'public_profile',
        'theme',
        'language',
        'timezone',
        'role',
        'plan_id',
        'subscription_suspended_at',
        'suspended_plan_id',
        'subscription_suspend_reason',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'facebook_page_access_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'facebook_page_access_token' => 'encrypted',
            'subscription_suspended_at' => 'datetime',
        ];
    }

    /**
     * Get the plan for this user
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function metaPages()
    {
        return $this->hasMany(MetaPage::class);
    }

    public function enterpriseMembership()
    {
        return $this->hasOne(EnterpriseMembership::class);
    }

    public function activeEnterpriseMembership(): ?EnterpriseMembership
    {
        $membership = $this->enterpriseMembership()->with('enterprise')->first();

        return $membership && $membership->isActive() && $membership->enterprise?->isActive()
            ? $membership
            : null;
    }

    public function teamMemberships()
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function primaryTeamMembership()
    {
        return $this->hasOne(TeamMembership::class)->where('is_primary', true);
    }

    public function assignedEnterpriseLeads()
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function enterpriseRole(): ?string
    {
        return $this->role === 'enterprise'
            ? $this->enterpriseMembership?->role
            : null;
    }

    public function isEnterpriseAdmin(): bool
    {
        return $this->enterpriseRole() === EnterpriseMembership::ROLE_ADMIN;
    }

    public function isEnterpriseManager(): bool
    {
        return $this->enterpriseRole() === EnterpriseMembership::ROLE_MANAGER;
    }

    public function isEnterpriseEmployee(): bool
    {
        return $this->enterpriseRole() === EnterpriseMembership::ROLE_EMPLOYEE;
    }

    public function suspendedPlan()
    {
        return $this->belongsTo(Plan::class, 'suspended_plan_id');
    }

    public function isSubscriptionSuspended(): bool
    {
        return $this->subscription_suspended_at !== null;
    }

    public function suspendSubscription(?string $reason = null): void
    {
        if ($this->isSubscriptionSuspended()) {
            return;
        }

        $this->suspended_plan_id = $this->plan_id;
        $this->subscription_suspended_at = now();
        $this->subscription_suspend_reason = $reason;

        $basicPlan = Plan::where('key', 'basic')->first();
        if ($basicPlan) {
            $this->plan_id = $basicPlan->id;
        }

        $this->save();
    }

    public function resumeSubscription(): void
    {
        if (!$this->isSubscriptionSuspended()) {
            return;
        }

        if ($this->suspended_plan_id) {
            $this->plan_id = $this->suspended_plan_id;
        }

        $this->subscription_suspended_at = null;
        $this->suspended_plan_id = null;
        $this->subscription_suspend_reason = null;
        $this->save();
    }

    /**
     * Get campaigns for this user
     */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function inventoryProjects()
    {
        return $this->hasMany(InventoryProject::class);
    }

    /**
     * Check if user can create a new campaign
     */
    public function canCreateCampaign(): bool
    {
        if ($this->isSubscriptionSuspended()) {
            return false;
        }

        if (!$this->plan) {
            return false;
        }

        return $this->plan->canCreateCampaign($this);
    }

    /**
     * Check if user can add lead to campaign
     */
    public function canAddLeadToCampaign(Campaign $campaign): bool
    {
        if ($this->isSubscriptionSuspended()) {
            return false;
        }

        if (!$this->plan) {
            return false;
        }

        return $this->plan->canAddLead($campaign);
    }

    /**
     * Get avatar URL or initials
     * Works with or without storage symlink
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return $this->google_avatar ?: ($this->facebook_avatar ?: null);
        }
        
        // Method 1: Check if file is accessible via public/storage (symlink works)
        $publicPath = public_path('storage/' . $this->avatar);
        if (file_exists($publicPath) && is_readable($publicPath)) {
            return asset('storage/' . $this->avatar);
        }
        
        // Method 2: Check if file exists in storage and use fallback route
        if (\Storage::disk('public')->exists($this->avatar)) {
            $filename = basename($this->avatar);
            return url('/image/avatars/' . $filename);
        }
        
        return null;
    }

    /**
     * Get avatar initials
     */
    public function getAvatarInitialsAttribute(): string
    {
        return strtoupper(substr($this->name, 0, 1));
    }

    /**
     * Get notifications for this user
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get unread notifications count
     */
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->unread()->count();
    }

    /**
     * Get subscriptions for this user
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get invoices for this user
     */
    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Subscription::class);
    }

    /**
     * Get payments for this user
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Subscription::class);
    }

}
