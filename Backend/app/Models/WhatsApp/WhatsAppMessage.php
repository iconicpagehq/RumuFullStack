<?php

namespace App\Models\WhatsApp;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'company_id',
        'whatsapp_conversation_id',
        'meta_message_id',
        'direction',
        'sender_id',
        'recipient_id',
        'type',
        'content',
        'media_url',
        'media_mime_type',
        'raw_payload',
        'status',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsAppMessageLog::class, 'whatsapp_message_id');
    }
}
