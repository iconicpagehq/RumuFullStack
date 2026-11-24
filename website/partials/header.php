<?php
$navItems = [
    ['key' => 'realtors', 'label' => 'For Realtors', 'href' => 'for-realtors'],
    ['key' => 'enterprises', 'label' => 'For Enterprises', 'href' => 'for-enterprises'],
    ['key' => 'features', 'label' => 'Features', 'href' => 'features'],
    ['key' => 'pricing', 'label' => 'Pricing', 'href' => 'pricing'],
];
?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e(site_url('/')) ?>" aria-label="Rumu home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 64 64" focusable="false">
                    <rect width="64" height="64" rx="14" fill="#E9B8DF"/>
                    <path d="M32 9 8 27.5V54h18V39a6 6 0 0 1 12 0v15h18V27.5L32 9Z" fill="#650052"/>
                    <path d="M26 54V39a6 6 0 0 1 12 0v15Z" fill="#E9B8DF"/>
                </svg>
            </span>
            <span class="brand-word">Rumu</span>
        </a>

        <nav class="site-nav" id="site-nav" aria-label="Primary">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e(site_url($item['href'])) ?>"<?= is_active_nav($item['key'], $navKey) ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>
            <a class="nav-login" href="<?= e(login_url()) ?>" data-event="login_click">Log in</a>
        </nav>

        <div class="header-actions">
            <a class="btn btn-ghost btn-sm header-login" href="<?= e(login_url()) ?>" data-event="login_click">Log in</a>
            <a class="btn btn-primary btn-sm" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="header-start">Start free</a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
                <span class="sr-only">Open navigation</span>
                <span class="nav-toggle-icon" aria-hidden="true"><?= icon('menu', 'icon nav-open-icon') ?><?= icon('x', 'icon nav-close-icon') ?></span>
            </button>
        </div>
    </div>
</header>
