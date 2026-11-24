<?php

namespace App\Models\WhatsApp;

use App\Models\Enterprise;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'company_id',
        'lead_id',
        'wa_id',
        'phone_number',
        'name',
        'profile_picture_url',
        'profile_data',
        'last_active_at',
    ];

    protected $casts = [
        'profile_data' => 'array',
        'last_active_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'whatsapp_contact_id');
    }
}
