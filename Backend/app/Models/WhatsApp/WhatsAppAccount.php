<?php

namespace App\Models\WhatsApp;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'company_id',
        'user_id',
        'waba_id',
        'phone_number_id',
        'display_phone_number',
        'verified_name',
        'code_verification_status',
        'quality_rating',
        'status',
        'access_token',
        'app_secret',
        'webhook_verify_token',
        'api_version',
        'is_default',
        'settings',
        'last_synced_at',
    ];

    protected $hidden = [
        'access_token',
        'app_secret',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'app_secret' => 'encrypted',
        'is_default' => 'boolean',
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'whatsapp_account_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsAppTemplate::class, 'whatsapp_account_id');
    }
}
