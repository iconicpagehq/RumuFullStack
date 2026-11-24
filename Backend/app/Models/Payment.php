<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'payment_method',
        'transaction_id',
        'amount',
        'gateway_signature',
        'webhook_payload',
        'verified_at',
        'coupon_id',
        'currency',
        'payment_status',
        'payment_date',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
