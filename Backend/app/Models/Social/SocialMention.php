<?php

namespace App\Models\Social;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMention extends Model
{
    use HasFactory;

    protected $table = 'social_mentions';

    protected $fillable = [
        'company_id',
        'user_id',
        'social_page_id',
        'platform',
        'external_id',
        'media_id',
        'comment_id',
        'mention_type',
        'media_url',
        'caption',
        'author_username',
        'mentioned_at',
        'raw_data',
    ];

    protected $casts = [
        'mentioned_at' => 'datetime',
        'raw_data' => 'array',
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

    public function page()
    {
        return $this->belongsTo(SocialPage::class, 'social_page_id');
    }

    public function scopeForTenant($query, ?int $companyId, int $userId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query->where('user_id', $userId)->whereNull('company_id');
    }
}

