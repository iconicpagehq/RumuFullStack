<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enterprise_id',
        'page_id',
        'page_name',
        'access_token',
        'status', // connected, expired, disconnected
        'token_last_synced_at',
        'consecutive_failures',
        'last_successful_api_request_at',
        'last_failed_api_request_at',
    ];

    protected $casts = [
        'token_last_synced_at' => 'datetime',
        'last_successful_api_request_at' => 'datetime',
        'last_failed_api_request_at' => 'datetime',
        'access_token' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }
}
