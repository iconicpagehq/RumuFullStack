<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    use HasFactory;

    public const METRICS = [
        'leads_assigned',
        'leads_contacted',
        'leads_qualified',
        'site_visits_completed',
        'deals_won',
        'deal_value',
        'revenue_received',
    ];

    protected $fillable = [
        'enterprise_id',
        'team_id',
        'user_id',
        'period_month',
        'metric',
        'target_value',
        'created_by',
    ];

    protected $casts = [
        'period_month' => 'date',
        'target_value' => 'decimal:2',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
