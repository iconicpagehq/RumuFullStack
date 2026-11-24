<?php

namespace App\Models\Social;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialConversation extends Model
{
    use HasFactory;

    protected $table = 'social_conversations';

    protected $fillable = [
        'company_id',
        'user_id',
        'social_page_id',
        'platform',
        'external_id',
        'participant_id',
        'participant_name',
        'participant_username',
        'participant_avatar',
        'unread_count',
        'last_message_text',
        'last_message_at',
        'is_assigned_to',
        'internal_notes',
    ];

    protected $casts = [
        'unread_count' => 'integer',
        'last_message_at' => 'datetime',
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

    public function page()
    {
        return $this->belongsTo(SocialPage::class, 'social_page_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'is_assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(SocialMessage::class, 'social_conversation_id');
    }

    public function scopeForTenant($query, ?int $companyId, int $userId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query->where('user_id', $userId)->whereNull('company_id');
    }
}

