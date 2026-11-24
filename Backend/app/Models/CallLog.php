<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CallLog extends Model
{
    use HasFactory, SoftDeletes;

    public const EDITION_REALTOR = 'realtor';
    public const EDITION_ENTERPRISE = 'enterprise';

    public const STATUS_INITIATING = 'initiating';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_ANSWER = 'no_answer';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';

    public const STATUSES = [
        self::STATUS_INITIATING,
        self::STATUS_RINGING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_NO_ANSWER,
        self::STATUS_FAILED,
        self::STATUS_CANCELED,
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_NO_ANSWER,
        self::STATUS_FAILED,
        self::STATUS_CANCELED,
    ];

    public const DISPOSITIONS = [
        'connected',
        'not_connected',
        'busy',
        'wrong_number',
        'call_back',
        'interested',
        'not_interested',
        'converted',
        'other',
    ];

    public const RECORDING_NONE = 'none';
    public const RECORDING_PENDING = 'pending';
    public const RECORDING_AVAILABLE = 'available';
    public const RECORDING_FAILED = 'failed';
    public const RECORDING_EXPIRED = 'expired';

    public const RECORDING_STATUSES = [
        self::RECORDING_NONE,
        self::RECORDING_PENDING,
        self::RECORDING_AVAILABLE,
        self::RECORDING_FAILED,
        self::RECORDING_EXPIRED,
    ];

    public const SOURCE_APP = 'app';
    public const SOURCE_IMPORT = 'import';

    // `web` and `webhook` are legacy values kept for old rows only.
    public const SOURCES = ['app', 'web', 'import', 'webhook'];

    public const MATCHED_VIA_AUTO = 'auto';
    public const MATCHED_VIA_MANUAL = 'manual';
    public const MATCHED_VIA_SESSION = 'session';

    public const MATCHED_VIA = [
        self::MATCHED_VIA_AUTO,
        self::MATCHED_VIA_MANUAL,
        self::MATCHED_VIA_SESSION,
    ];

    protected $fillable = [
        'uuid',
        'edition',
        'realtor_user_id',
        'enterprise_id',
        'enterprise_membership_id',
        'team_id',
        'initiated_by',
        'lead_id',
        'campaign_id',
        'lead_name',
        'lead_phone',
        'provider',
        'provider_call_id',
        'direction',
        'source',
        'status',
        'disposition',
        'disposition_notes',
        'from_number',
        'to_number',
        'started_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
        'recording_status',
        'recording_disk',
        'recording_path',
        'recording_mime',
        'recording_size',
        'recording_duration_seconds',
        'recording_sha256',
        'recording_deleted_at',
        'retention_hold',
        'consent_announced',
        'cost',
        'provider_payload',
        'client_call_id',
        'device_uuid',
        'consent_confirmed_at',
        'uploaded_at',
        'matched_via',
        'client_metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'recording_deleted_at' => 'datetime',
        'retention_hold' => 'boolean',
        'consent_announced' => 'boolean',
        'consent_confirmed_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'duration_seconds' => 'integer',
        'recording_size' => 'integer',
        'recording_duration_seconds' => 'integer',
        'cost' => 'decimal:4',
        'provider_payload' => 'array',
        'client_metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (CallLog $call) {
            if (empty($call->uuid)) {
                $call->uuid = (string) Str::uuid();
            }
        });
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function realtor()
    {
        return $this->belongsTo(User::class, 'realtor_user_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function membership()
    {
        return $this->belongsTo(EnterpriseMembership::class, 'enterprise_membership_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function webhookEvents()
    {
        return $this->hasMany(CallWebhookEvent::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(CallRecordingAccessLog::class);
    }

    public function scopeForRealtor($query, int $userId)
    {
        return $query->where('edition', self::EDITION_REALTOR)
            ->where('realtor_user_id', $userId);
    }

    public function scopeForEnterprise($query, int $enterpriseId)
    {
        return $query->where('edition', self::EDITION_ENTERPRISE)
            ->where('enterprise_id', $enterpriseId);
    }

    public function scopeWithLeadRelation($query)
    {
        return $query->with(['lead:id,name,phone,campaign_id', 'initiator:id,name,avatar', 'team:id,name']);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function hasRecordedAudio(): bool
    {
        return $this->recording_status === self::RECORDING_AVAILABLE
            && $this->recording_path
            && $this->recording_deleted_at === null;
    }

    public function isAppSource(): bool
    {
        return $this->source === self::SOURCE_APP;
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_APP => 'Android app',
            self::SOURCE_IMPORT => 'Uploaded recording',
            'web' => 'Dashboard',
            'webhook' => 'Provider',
            default => 'Unknown',
        };
    }

    public function recordingUrlAvailable(): bool
    {
        return $this->recording_status === self::RECORDING_PENDING;
    }

    public static function statusRank(string $status): int
    {
        return match ($status) {
            self::STATUS_INITIATING => 10,
            self::STATUS_RINGING => 20,
            self::STATUS_IN_PROGRESS => 30,
            self::STATUS_COMPLETED => 40,
            self::STATUS_NO_ANSWER => 50,
            self::STATUS_CANCELED => 51,
            self::STATUS_FAILED => 52,
            default => 0,
        };
    }

    public static function agentName(?self $call): ?string
    {
        return $call?->initiator?->name;
    }
}
