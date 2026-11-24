<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCallProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_number',
        'auto_record',
        'consent_notice',
    ];

    protected $casts = [
        'auto_record' => 'boolean',
        'consent_notice' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
