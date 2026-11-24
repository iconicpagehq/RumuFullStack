<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rumu app calling
    |--------------------------------------------------------------------------
    |
    | Calls are placed on the handset SIM from the Android app. The app records
    | the call session locally, finds the handset's own recording file, and
    | uploads it here. There is no cloud bridge and no per-minute provider.
    |
    | `config/telephony.php` is kept only for legacy rows and will be removed
    | during cleanup; new code must read from this file.
    |
    */

    'recordings' => [
        'disk' => env('CALL_RECORDING_DISK', 'local'),
        'retention_days' => (int) env('CALL_RECORDING_RETENTION_DAYS', 90),
        'max_bytes' => (int) env('CALL_RECORDING_MAX_BYTES', 52428800),
        'extensions' => ['mp3', 'wav', 'm4a', 'mp4', 'ogg', 'amr', 'aac', '3gp', 'webm'],
    ],

    'limits' => [
        'daily_per_user' => (int) env('CALL_DAILY_LIMIT_PER_USER', 50),
        // How far back the app may backfill calls it found locally.
        'backfill_days' => (int) env('CALL_BACKFILL_DAYS', 180),
    ],

    'consent' => [
        'copy_version' => '2026-11-01',
        'required' => true,
    ],

    'app' => [
        // Shown in the app so older builds can prompt for an update.
        'min_version' => env('CALL_APP_MIN_VERSION', '1.0.0'),
    ],
];
