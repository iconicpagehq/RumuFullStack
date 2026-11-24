<?php

namespace App\Models\WhatsApp;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageLog extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_message_logs';

    protected $fillable = [
        'company_id',
        'whatsapp_message_id',
        'api_endpoint',
        'http_method',
        'http_status',
        'request_payload',
        'response_payload',
        'error_details',
        'duration_ms',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'duration_ms' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}
