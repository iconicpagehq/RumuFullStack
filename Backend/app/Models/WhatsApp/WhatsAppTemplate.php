<?php

namespace App\Models\WhatsApp;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'company_id',
        'whatsapp_account_id',
        'meta_template_id',
        'name',
        'language',
        'category',
        'status',
        'components',
        'sample_values',
        'rejected_reason',
        'last_synced_at',
    ];

    protected $casts = [
        'components' => 'array',
        'sample_values' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'company_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }
}
