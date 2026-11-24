<?php

namespace App\Models\WhatsApp;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'company_id',
        'whatsapp_account_id',
        'whatsapp_contact_id',
        'assigned_to',
        'status',
        'session_expires_at',
        'unread_count',
        'last_message_text',
        'last_message_at',
        'last_incoming_at',
        'last_outgoing_at',
        'metadata',
    ];

    protected $casts = [
        'session_expires_at' => 'datetime',
        'unread_count' => 'integer',
        'last_message_at' => 'datetime',
        'last_incoming_at' => 'datetime',
        'last_outgoing_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'whatsapp_contact_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_conversation_id');
    }

    /**
     * Check if the 24-hour service conversation window with Meta is still active.
     */
    public function isSessionActive(): bool
    {
        return $this->session_expires_at !== null && $this->session_expires_at->isFuture();
    }
}
