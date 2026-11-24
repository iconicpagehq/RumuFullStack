<?php

namespace App\Models\Social;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialInsight extends Model
{
    use HasFactory;

    protected $table = 'social_insights';

    protected $fillable = [
        'company_id',
        'user_id',
        'social_page_id',
        'platform',
        'metric_type',
        'period',
        'metrics',
        'recorded_date',
    ];

    protected $casts = [
        'metrics' => 'array',
        'recorded_date' => 'date',
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

