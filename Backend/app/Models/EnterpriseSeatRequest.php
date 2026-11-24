<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterpriseSeatRequest extends Model
{
    public const SEAT_MANAGERS = 'managers';
    public const SEAT_EMPLOYEES = 'employees';

    public const SEAT_TYPES = [
        self::SEAT_MANAGERS,
        self::SEAT_EMPLOYEES,
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'enterprise_id',
        'seat_type',
        'quantity',
        'status',
        'note',
        'admin_notes',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function seatLabel(): string
    {
        return $this->seat_type === self::SEAT_MANAGERS ? 'manager seats' : 'employee seats';
    }
}
