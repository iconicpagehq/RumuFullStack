<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaCapiSetting extends Model
{
    protected $fillable = [
        'campaign_id',
        'pixel_id',
        'access_token',
        'test_event_code',
        'status_mapping',
        'is_active',
    ];

    protected $casts = [
        'status_mapping' => 'array',
        'is_active' => 'boolean',
        'access_token' => 'encrypted',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
