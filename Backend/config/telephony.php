<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rumu calling (cloud telephony)
    |--------------------------------------------------------------------------
    |
    | Calls are bridged and recorded by the provider, never on the handset.
    | Keep this disabled until the provider account, KYC, and legal consent
    | copy are ready. The import fallback keeps calls usable without it.
    |
    */

    'enabled' => (bool) env('TELEPHONY_ENABLED', false),

    'provider' => env('TELEPHONY_PROVIDER', 'exotel'),

    'webhook' => [
        'secret' => env('TELEPHONY_WEBHOOK_SECRET'),
        'signature_header' => env('TELEPHONY_SIGNATURE_HEADER', 'X-Rumu-Signature'),
    ],

    'exotel' => [
        'subdomain' => env('EXOTEL_SUBDOMAIN'),
        'account_sid' => env('EXOTEL_ACCOUNT_SID'),
        'api_key' => env('EXOTEL_API_KEY'),
        'api_token' => env('EXOTEL_API_TOKEN'),
        'caller_id' => env('EXOTEL_CALLER_ID'),
        'status_callback_url' => env('TELEPHONY_STATUS_CALLBACK_URL'),
        'record' => (bool) env('EXOTEL_RECORD_CALLS', true),
        'time_limit' => (int) env('EXOTEL_CALL_TIME_LIMIT', 14400),
        'timeout' => (int) env('EXOTEL_CALL_TIMEOUT', 45),
    ],

    'recordings' => [
        'disk' => env('CALL_RECORDING_DISK', 'local'),
        'retention_days' => (int) env('CALL_RECORDING_RETENTION_DAYS', 90),
        'max_bytes' => (int) env('CALL_RECORDING_MAX_BYTES', 52428800),
    ],

    'limits' => [
        'daily_per_user' => (int) env('CALL_DAILY_LIMIT_PER_USER', 50),
    ],
];
