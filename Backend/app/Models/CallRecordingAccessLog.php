<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallRecordingAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_log_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public function call()
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
