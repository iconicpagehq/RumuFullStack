<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Plans
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for available billing plans in the RUMU CRM.
    |
    */
    'plans' => [
        'free' => [
            'name' => 'Free',
            'duration_months' => 0,
        ],
        'rumu_pro' => [
            'name' => 'RUMU Pro',
            'duration_months' => 12,
        ],
    ],
];
