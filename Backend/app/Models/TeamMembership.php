<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'team_id',
        'user_id',
        'is_primary',
        'last_assigned_at',
        'joined_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_assigned_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
