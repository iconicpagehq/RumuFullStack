<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_purchase_amount',
        'applicable_plans',
        'start_date',
        'expiration_date',
        'usage_limit',
        'customer_usage_limit',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'applicable_plans' => 'array',
        'start_date' => 'datetime',
        'expiration_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }
}
