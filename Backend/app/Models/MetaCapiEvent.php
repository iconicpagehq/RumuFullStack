<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaCapiEvent extends Model
{
    protected $fillable = [
        'lead_id',
        'campaign_id',
        'event_name',
        'event_id',
        'status',
        'response',
        'sent_at',
    ];

    protected $casts = [
        'response' => 'array',
        'sent_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
