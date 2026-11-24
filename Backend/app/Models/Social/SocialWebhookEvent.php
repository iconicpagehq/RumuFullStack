<?php

namespace App\Models\Social;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'social_webhook_events';

    protected $fillable = [
        'event_id',
        'platform',
        'event_type',
        'payload',
        'processed',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];
}

