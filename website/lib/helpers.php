<?php

require_once __DIR__ . '/api.php';

function config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Base URL path of the website directory (supports XAMPP subfolders and a vhost root).
 */
function site_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $base = '';
    $root = realpath(dirname(__DIR__));
    $doc = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($root && $doc) {
        $rootNorm = str_replace('\\', '/', $root);
        $docNorm = rtrim(str_replace('\\', '/', $doc), '/');
        if ($docNorm !== '' && stripos($rootNorm, $docNorm) === 0) {
            $base = rtrim(substr($rootNorm, strlen($docNorm)), '/');
        }
    }
    return $base;
}

function site_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if ($path === '' || $path === '/') {
        return site_base_path() . '/';
    }
    return site_base_path() . '/' . ltrim($path, '/');
}

function canonical_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return config('site_url') . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    $file = dirname(__DIR__) . '/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return site_url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

/**
 * Absolute asset URL for metadata consumers (Open Graph, schema.org) that require a full URL.
 */
function absolute_asset_url(string $path): string
{
    $file = dirname(__DIR__) . '/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return rtrim(config('site_url'), '/') . '/assets/' . ltrim($path, '/') . '?v=' . $version;
}

function current_utm(): array
{
    $allowed = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'ref'];
    $utm = [];
    foreach ($allowed as $key) {
        $value = $_GET[$key] ?? null;
        if (is_string($value) && $value !== '' && strlen($value) <= 120) {
            $utm[$key] = $value;
        }
    }
    return $utm;
}

function with_utm(string $url): string
{
    $utm = current_utm();
    if ($utm === []) {
        return $url;
    }
    $fragment = '';
    if (str_contains($url, '#')) {
        [$url, $fragment] = explode('#', $url, 2);
        $fragment = '#' . $fragment;
    }
    $separator = str_contains($url, '?') ? '&' : '?';
    return $url . $separator . http_build_query($utm) . $fragment;
}

function login_url(): string
{
    return with_utm(config('app_url') . '/login');
}

function register_url(string $type = 'realtor', ?array $plan = null): string
{
    $type = $type === 'enterprise' ? 'enterprise' : 'realtor';
    $url = config('app_url') . '/register?type=' . $type;
    if ($type === 'realtor' && $plan && !empty($plan['id'])) {
        $url .= '&plan=' . (int) $plan['id'];
    }
    return with_utm($url);
}

function plan_register_url(array $plan): string
{
    return register_url(($plan['audience'] ?? 'realtor') === 'enterprise' ? 'enterprise' : 'realtor', $plan);
}

function mailto_url(string $subject = 'Rumu website enquiry'): string
{
    return 'mailto:' . config('support_email') . '?subject=' . rawurlencode($subject);
}

function format_price($amount, string $currency = 'INR'): string
{
    $amount = (float) $amount;
    $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;
    $number = number_format($amount, $decimals, '.', ',');
    if (strtoupper($currency) === 'INR') {
        return '₹' . $number;
    }
    return strtoupper($currency) . ' ' . $number;
}

function plan_price(array $plan, string $currency = 'INR'): string
{
    $price = (float) ($plan['price'] ?? 0);
    return $price <= 0 ? 'Free' : format_price($price, $currency);
}

function plan_limit($value, string $suffix = ''): string
{
    if ($value === null || $value === '') {
        return 'Unlimited';
    }
    return number_format((int) $value) . ($suffix !== '' ? ' ' . $suffix : '');
}

function is_enterprise_plan(array $plan): bool
{
    return ($plan['audience'] ?? 'realtor') === 'enterprise';
}

function plan_summary_limits(array $plan): array
{
    if (is_enterprise_plan($plan)) {
        return [
            ['Managers', plan_limit($plan['max_managers'] ?? null)],
            ['Employees', plan_limit($plan['max_employees'] ?? null)],
            ['Teams', plan_limit($plan['max_teams'] ?? null)],
            ['Monthly leads', plan_limit($plan['max_monthly_leads'] ?? null)],
            ['Inventory units', plan_limit($plan['max_inventory_units'] ?? null)],
        ];
    }
    return [
        ['Campaigns', plan_limit($plan['max_campaigns'] ?? null)],
        ['Leads per campaign', plan_limit($plan['max_leads_per_campaign'] ?? null)],
    ];
}

function plan_badges(array $plan): array
{
    $badges = [];
    if (is_enterprise_plan($plan)) {
        $badges[] = '14-day trial';
    }
    if (!empty($plan['advanced_reports'])) {
        $badges[] = 'Advanced reports';
    }
    if (!empty($plan['meta_integrations'])) {
        $badges[] = 'Meta integrations';
    }
    return $badges;
}

function plan_cta_label(array $plan): string
{
    if ((float) ($plan['price'] ?? 0) <= 0) {
        return 'Start free';
    }
    return is_enterprise_plan($plan) ? 'Start 14-day trial' : 'Choose ' . ($plan['name'] ?? 'plan');
}

function plan_cta_url(array $plan): string
{
    return plan_register_url($plan);
}

function is_active_nav(string $key, string $current): string
{
    return $key === $current ? ' aria-current="page"' : '';
}

/** Inline icon set (paths adapted from Lucide, MIT). */
function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'bolt' => '<path d="M13 2 3 14h8l-1 8 10-12h-8l1-8Z"/>',
        'repeat' => '<path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M7 15v3"/><path d="M12 9v9"/><path d="M17 5v13"/>',
        'building' => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.34 1.78.66 2.61a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.47-1.23a2 2 0 0 1 2.11-.45c.83.32 1.71.54 2.61.66A2 2 0 0 1 22 16.92Z"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'shield' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1Z"/><path d="m9 12 2 2 4-4"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'layers' => '<path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
        'clipboard' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6M9 16h6"/>',
        'play' => '<path d="m6 3 15 9-15 9V3Z"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5M12 3v12"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'lock' => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'target' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/>',
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22V12h6v10"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/>',
        'mic' => '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v3"/>',
        'flag' => '<path d="M4 22V4c0-1.1.9-2 2-2h9l-1.5 5H20l-2 6H6.5"/><path d="M4 4h16"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'sliders' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'sparkles' => '<path d="m12 3-1.9 5.8L4 10.7l6.1 1.9L12 18.4l1.9-5.8 6.1-1.9-6.1-1.9Z"/><path d="M5 3v4M3 5h4M19 17v4M17 19h4"/>',
        'trend' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M17 7h4v4"/>',
    ];
    $content = $paths[$name] ?? $paths['check'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $content . '</svg>';
}

function json_ld_tag(array $data): string
{
    return '<script type="application/ld+json">' . json_encode(
        $data,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
    ) . '</script>';
}

function breadcrumb_schema(array $items): array
{
    $list = [];
    $position = 1;
    foreach ($items as $label => $path) {
        $list[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $label,
            'item' => canonical_url($path),
        ];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

function faq_schema(array $faqs): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
        ], $faqs),
    ];
}
