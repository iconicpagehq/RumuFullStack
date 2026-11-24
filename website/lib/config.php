<?php

/**
 * Site configuration. No secrets belong in this file.
 * Environment overrides are optional and read from the process environment.
 */
return [
    'site_name' => 'Rumu',
    'site_tagline' => 'Real-estate sales workspace',
    'site_url' => rtrim(getenv('RUMU_SITE_URL') ?: 'https://rumu.in', '/'),
    'app_url' => rtrim(getenv('RUMU_APP_URL') ?: 'https://app.rumu.in', '/'),
    'api_base' => rtrim(getenv('RUMU_API_BASE') ?: 'https://api.rumu.in/api/v1', '/'),
    'support_email' => getenv('RUMU_SUPPORT_EMAIL') ?: 'admin@rumu.in',
    'cache_dir' => dirname(__DIR__) . '/storage/cache',
    'cache_ttl' => 300,
    'api_timeout' => 3,
    'launch_date' => '1 November 2026',
];
