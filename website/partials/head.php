<?php
if (!function_exists('e')) {
    require_once dirname(__DIR__) . '/lib/helpers.php';
}

$page = $page ?? [];
$pageTitle = $page['title'] ?? 'Rumu — Real Estate CRM for Realtors & Builders | Leads, Calls, Follow-ups';
$pageDescription = $page['description'] ?? 'Rumu turns property enquiries into site visits. Capture leads, track calls, plan follow-ups, manage inventory and see team performance in one workspace.';
$canonicalPath = $page['canonical_path'] ?? '/';
$canonical = canonical_url($canonicalPath);
$bodyClass = trim('site ' . ($page['body_class'] ?? ''));
$ogImage = absolute_asset_url('img/og-cover.png');
$navKey = $page['nav'] ?? '';

$organisation = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'Rumu',
    'url' => config('site_url'),
    'logo' => absolute_asset_url('img/og-cover.png'),
    'description' => 'Real-estate sales workspace for Indian realtors and enterprise builder teams.',
    'email' => config('support_email'),
    'areaServed' => 'IN',
];

$schemas = [$organisation];
foreach (($page['json_ld'] ?? []) as $schema) {
    if (is_array($schema)) {
        $schemas[] = $schema;
    }
}
?>
<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#8A006B">
    <meta name="robots" content="<?= e($page['robots'] ?? 'index, follow') ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">

    <meta property="og:type" content="<?= e($page['og_type'] ?? 'website') ?>">
    <meta property="og:site_name" content="Rumu">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:locale" content="en_IN">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <link rel="icon" href="<?= e(asset_url('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= e(asset_url('css/site.css')) ?>">
    <noscript><style>.tab-panel[hidden]{display:block!important}.edition-panel[hidden]{display:block!important}</style></noscript>
    <?php foreach ($schemas as $schema): ?>
        <?= json_ld_tag($schema) ?>
    <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>" data-page="<?= e($page['page_id'] ?? '') ?>">
<a class="skip-link" href="#main">Skip to content</a>
