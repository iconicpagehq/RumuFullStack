<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingSettingAudit extends Model
{
    use HasFactory;

    protected $table = 'billing_settings_audits';

    protected $fillable = [
        'user_id',
        'setting_key',
        'old_value',
        'new_value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
