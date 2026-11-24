<?php

namespace App\Models\Social;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialComment extends Model
{
    use HasFactory;

    protected $table = 'social_comments';

    protected $fillable = [
        'company_id',
        'user_id',
        'social_post_id',
        'parent_comment_id',
        'platform',
        'external_id',
        'from_id',
        'from_name',
        'message',
        'is_hidden',
        'is_deleted',
        'created_time',
        'raw_data',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'is_deleted' => 'boolean',
        'created_time' => 'datetime',
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

    public function post()
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function parent()
    {
        return $this->belongsTo(SocialComment::class, 'parent_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(SocialComment::class, 'parent_comment_id');
    }

    public function scopeForTenant($query, ?int $companyId, int $userId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query->where('user_id', $userId)->whereNull('company_id');
    }
}

