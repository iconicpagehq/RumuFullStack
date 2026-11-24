<?php

namespace App\Models\Social;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialSyncLog extends Model
{
    use HasFactory;

    protected $table = 'social_sync_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'social_page_id',
        'platform',
        'sync_type',
        'status',
        'items_synced',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'items_synced' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
}

