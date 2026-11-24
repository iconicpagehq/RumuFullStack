<?php

namespace App\Models\Social;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialPage extends Model
{
    use HasFactory;

    protected $table = 'social_pages';

    protected $fillable = [
        'company_id',
        'user_id',
        'social_account_id',
        'platform',
        'external_id',
        'name',
        'username',
        'category',
        'instagram_business_account_id',
        'access_token',
        'is_active',
        'last_synced_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function posts()
    {
        return $this->hasMany(SocialPost::class, 'social_page_id');
    }

    public function conversations()
    {
        return $this->hasMany(SocialConversation::class, 'social_page_id');
    }

    public function mentions()
    {
        return $this->hasMany(SocialMention::class, 'social_page_id');
    }

    public function insights()
    {
        return $this->hasMany(SocialInsight::class, 'social_page_id');
    }

    public function syncLogs()
    {
        return $this->hasMany(SocialSyncLog::class, 'social_page_id');
    }

    public function scopeForTenant($query, ?int $companyId, int $userId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query->where('user_id', $userId)->whereNull('company_id');
    }
}

