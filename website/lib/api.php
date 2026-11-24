<?php

/**
 * Public marketing-site API client.
 * Only unauthenticated GET endpoints are allowed here, and nothing is cached
 * beyond its freshness window: stale plan data must never be rendered.
 */

function api_cache_path(string $path): string
{
    $dir = config('cache_dir');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . '/' . sha1($path) . '.json';
}

function api_cache_read(string $path, int $ttl): ?array
{
    $file = api_cache_path($path);
    if (!is_file($file)) {
        return null;
    }
    $age = time() - (int) @filemtime($file);
    if ($age < 0 || $age > $ttl) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false) {
        return null;
    }
    $cached = json_decode($raw, true);
    return is_array($cached) && isset($cached['body']) ? $cached['body'] : null;
}

function api_cache_write(string $path, array $body): void
{
    $file = api_cache_path($path);
    $payload = json_encode(['time' => time(), 'body' => $body], JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return;
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $payload, LOCK_EX) !== false) {
        @rename($tmp, $file);
    }
    if (is_file($tmp)) {
        @unlink($tmp);
    }
}

/**
 * GET a public API path and return a normalised result:
 * ['ok' => bool, 'data' => mixed, 'error' => ?string].
 */
function api_get(string $path, ?int $ttl = null): array
{
    $path = '/' . ltrim($path, '/');
    $ttl = $ttl ?? (int) config('cache_ttl');

    $cached = api_cache_read($path, $ttl);
    if (is_array($cached)) {
        return ['ok' => (bool) ($cached['success'] ?? false), 'data' => $cached['data'] ?? null, 'error' => null];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'data' => null, 'error' => 'curl_unavailable'];
    }

    $url = config('api_base') . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => max(1, (int) config('api_timeout')),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: RumuMarketingSite/1.0 (+' . config('site_url') . ')',
        ],
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $status < 200 || $status >= 300) {
        return ['ok' => false, 'data' => null, 'error' => $error !== '' ? $error : 'http_' . $status];
    }

    $body = json_decode($raw, true);
    if (!is_array($body) || !($body['success'] ?? false)) {
        return ['ok' => false, 'data' => null, 'error' => 'invalid_payload'];
    }

    $normalised = ['success' => true, 'data' => $body['data'] ?? null];
    api_cache_write($path, $normalised);

    return ['ok' => true, 'data' => $normalised['data'], 'error' => null];
}

/**
 * Load plans for pricing. Returns:
 * ['ok' => bool, 'plans' => ['realtor' => [], 'enterprise' => []], 'currency' => string, 'tax' => array]
 */
function fetch_plans(): array
{
    $result = api_get('/checkout/plans');

    if (!$result['ok'] || !is_array($result['data']['plans'] ?? null)) {
        return ['ok' => false, 'plans' => ['realtor' => [], 'enterprise' => []], 'currency' => 'INR', 'tax' => []];
    }

    $payload = $result['data'];
    $plans = array_values(array_filter($payload['plans'], static fn ($plan) => is_array($plan) && !empty($plan['is_active'])));
    usort($plans, static fn ($a, $b) => [(int) ($a['sort_order'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort_order'] ?? 0), (int) ($b['id'] ?? 0)]);

    $realtor = [];
    $enterprise = [];
    foreach ($plans as $plan) {
        $audience = $plan['audience'] ?? 'realtor';
        if ($audience === 'enterprise') {
            $enterprise[] = $plan;
        } elseif ($audience === 'both') {
            $realtor[] = $plan;
            $enterprise[] = $plan;
        } else {
            $realtor[] = $plan;
        }
    }

    // The public enterprise endpoint is a safety net if /checkout/plans omits enterprise tiers.
    if ($enterprise === []) {
        $ent = api_get('/enterprise/plans');
        if ($ent['ok'] && is_array($ent['data'])) {
            $enterprise = array_values(array_filter($ent['data'], static fn ($plan) => is_array($plan)));
        }
    }

    return [
        'ok' => true,
        'plans' => ['realtor' => $realtor, 'enterprise' => $enterprise],
        'currency' => $payload['currency'] ?? 'INR',
        'tax' => is_array($payload['tax'] ?? null) ? $payload['tax'] : [],
    ];
}
