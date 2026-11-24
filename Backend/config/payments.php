<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment connection that gets used while
    | processing payments. Supported: "razorpay"
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'razorpay'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('PAYMENT_CURRENCY', 'INR'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */

    'gateways' => [
        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY', ''),
            'key_secret' => env('RAZORPAY_SECRET', ''),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', ''),
        ],
    ],

];
