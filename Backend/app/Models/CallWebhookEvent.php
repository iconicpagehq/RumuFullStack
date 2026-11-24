<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_id',
        'call_log_id',
        'provider_call_id',
        'event_type',
        'status',
        'payload',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function call()
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }
}
