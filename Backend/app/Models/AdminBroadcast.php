<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminBroadcast extends Model
{
    public const AUDIENCE_ALL_REALTORS = 'all_realtors';
    public const AUDIENCE_ENTERPRISE_ADMINS = 'all_enterprise_admins';
    public const AUDIENCE_ENTERPRISE_MEMBERS = 'all_enterprise_members';
    public const AUDIENCE_ENTERPRISE = 'enterprise';

    public const AUDIENCES = [
        self::AUDIENCE_ALL_REALTORS,
        self::AUDIENCE_ENTERPRISE_ADMINS,
        self::AUDIENCE_ENTERPRISE_MEMBERS,
        self::AUDIENCE_ENTERPRISE,
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'audience',
        'enterprise_id',
        'recipients_count',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'recipients_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }
}
