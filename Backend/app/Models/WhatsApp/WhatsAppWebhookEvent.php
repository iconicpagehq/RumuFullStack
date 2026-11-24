<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'payload',
        'status',
        'error',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
