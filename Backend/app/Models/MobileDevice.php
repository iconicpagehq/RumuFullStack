<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileDevice extends Model
{
    use HasFactory;

    public const PLATFORM_ANDROID = 'android';

    protected $fillable = [
        'user_id',
        'device_uuid',
        'platform',
        'model',
        'os_version',
        'app_version',
        'recorder_source',
        'recording_auto_sync',
        'last_seen_at',
    ];

    protected $casts = [
        'recording_auto_sync' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
