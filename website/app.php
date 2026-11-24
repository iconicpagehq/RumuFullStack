<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/partials/ui-mockups.php';

$planned = [
    ['Same account login', 'The same Rumu account and workspace, without migration later.'],
    ['Role-aware navigation', 'Employees, managers and admins see the sections their role allows.'],
    ['Lead view and updates', 'Check a lead, update its stage and add a note from the field.'],
    ['Tasks and site visits', 'See what is due and record a visit outcome.'],
    ['Call from the lead', 'Tap Call on a lead and dial from your own SIM — no cloud bridge, no call-audio permissions.'],
    ['Recording sync and notifications', 'After the call, log consent and outcome, then upload the handset recording to the workspace automatically.'],
];

$page = [
    'title' => 'Rumu Android App — Planned Roadmap (Web Works Today) | Rumu',
    'description' => 'The Rumu Android app is in development. The web workspace is live today with the same account and data. See the planned scope and start on web.',
    'canonical_path' => '/app',
    'nav' => 'features',
    'page_id' => 'app',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Android app' => '/app'])],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Android app · Roadmap</p>
            <span class="pill pill-warning" style="margin-bottom:16px">Coming soon — not shipped</span>
            <h1 id="page-title">Your desk, in your pocket. Jald aa raha hai.</h1>
            <p class="lede">The Rumu Android app is in development. Everything on this site works today in the web workspace — same account, same data, no migration later.</p>
            <div class="button-row button-row--center">
                <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="app-start-web">Start on web</a>
                <a class="btn btn-secondary btn-lg" href="<?= e(mailto_url('Notify me when the Rumu Android app launches')) ?>" data-event="cta_click" data-cta="app-notify">Email me at launch</a>
            </div>
            <p class="hero-proof" style="justify-content:center">
                <span><?= icon('check') ?> No store badge</span>
                <span><?= icon('check') ?> No fake app screenshots</span>
                <span><?= icon('check') ?> Wireframe only</span>
            </p>
        </div>
    </section>

    <section class="section" aria-labelledby="status-title">
        <div class="container split">
            <div>
                <p class="kicker">Status</p>
                <h2 id="status-title">The app is a plan, not a download.</h2>
                <p class="lede">We are not on the Play Store. There is no star rating, no “app available” claim and no mock screenshot pretending otherwise. The wireframe below shows intended structure, labelled as a preview.</p>
                <div class="callout">
                    <p><strong>What works today:</strong> the web panel is live and responsive. Use it on your phone browser while the native app is being built.</p>
                </div>
                <ul class="check-list" style="margin-top:20px">
                    <li><?= icon('check') ?><span>Same workspace and account as the web panel.</span></li>
                    <li><?= icon('check') ?><span>No data migration needed when the app ships.</span></li>
                    <li><?= icon('check') ?><span>Roadmap scope is described below, not sold as shipped.</span></li>
                </ul>
            </div>
            <div style="display:flex;justify-content:center">
                <?= ui_mockup('app-wire') ?>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="planned-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Planned scope</p>
                <h2 id="planned-title">What the app is meant to do.</h2>
                <p>These are planned capabilities from the Android plan. They are not available in an app today.</p>
            </div>
            <div class="grid-3">
                <?php foreach ($planned as [$title, $text]): ?>
                    <article class="card feature-card">
                        <div class="feature-meta"><span class="pill pill-warning">Planned</span></div>
                        <h3><?= e($title) ?></h3>
                        <p><?= e($text) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="today-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Do not wait</p>
                <h2 id="today-title">The work you need to do today is already supported.</h2>
            </div>
            <div class="grid-3">
                <article class="card">
                    <div class="card-icon"><?= icon('layers') ?></div>
                    <h3>Lead board</h3>
                    <p class="small muted">Every lead, stage and owner across campaigns, on a responsive web board.</p>
                    <a class="feature-link" href="<?= e(site_url('web-panel')) ?>#lead-board">See the board <?= icon('arrow-right') ?></a>
                </article>
                <article class="card">
                    <div class="card-icon"><?= icon('repeat') ?></div>
                    <h3>Follow-ups</h3>
                    <p class="small muted">Tasks and site visits with due dates and overdue flags.</p>
                    <a class="feature-link" href="<?= e(site_url('for-realtors')) ?>#follow-up">See follow-ups <?= icon('arrow-right') ?></a>
                </article>
                <article class="card">
                    <div class="card-icon"><?= icon('phone') ?></div>
                    <h3>Calls</h3>
                    <p class="small muted">Calls from the app will sync their recordings here. Until the app ships, use the web upload fallback.</p>
                    <a class="feature-link" href="<?= e(site_url('calls')) ?>">See calls <?= icon('arrow-right') ?></a>
                </article>
            </div>
            <div class="honest-note" style="margin-top:30px">
                <?= icon('flag') ?>
                <p><strong>No waitlist form:</strong> there is no live waitlist endpoint, so we do not pretend to collect signups. The email link opens your mail app and reaches support. Start on web now and your workspace will be ready for the app later.</p>
            </div>
        </div>
    </section>

    <?php
    $ctaTitle = 'Start on web today. No migration later.';
    $ctaText = 'Create your free workspace now; the app will use the same account and data when it ships.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'Open the web panel';
    $ctaSecondaryUrl = site_url('web-panel');
    $ctaNote = 'Web panel live · Android app in development';
    $ctaId = 'app-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
