<?php

namespace App\Models\Social;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMessage extends Model
{
    use HasFactory;

    protected $table = 'social_messages';

    protected $fillable = [
        'company_id',
        'user_id',
        'social_conversation_id',
        'platform',
        'external_id',
        'sender_id',
        'sender_name',
        'recipient_id',
        'message_text',
        'attachments',
        'is_from_page',
        'sent_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_from_page' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(SocialConversation::class, 'social_conversation_id');
    }

    public function scopeForTenant($query, ?int $companyId, int $userId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query->where('user_id', $userId)->whereNull('company_id');
    }
}

