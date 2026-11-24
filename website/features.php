<?php
require_once __DIR__ . '/lib/helpers.php';

$realtorFeatures = [
    ['Dashboard', 'KPI counters, conversion funnel, recent inquiries, campaign velocity and overdue follow-ups in one view.', 'web-panel#dashboard', 'chart'],
    ['Global Lead Board', 'Every lead across every campaign, in kanban and table view, with stage changes, search, filters and CSV export.', 'web-panel#lead-board', 'layers'],
    ['Campaigns', 'Create, edit and pause campaigns, set lead caps and schedules, and review per-campaign analytics.', 'web-panel', 'target'],
    ['Lead detail', 'Contact rail, WhatsApp / Note / Activity tiles, Calls tab, stage rail, timeline, notes, follow-ups and status audit.', 'for-realtors#lead-detail-title', 'clipboard'],
    ['Lead capture', 'Manual entry, CSV/XLSX/XLS import, Meta Lead Ads CSV import and real-time Meta Lead Gen sync.', 'for-realtors#meta', 'inbox'],
    ['Meta CAPI', 'Server-side conversion events on lead stage changes, so the ad platform receives a real signal.', 'for-realtors#meta', 'sparkles'],
    ['Inventory', 'Project → tower → wing → floor → unit availability, with BHK, price, floor and area filters.', 'web-panel#inventory', 'building'],
    ['Customers', 'Won leads become a persistent customer directory with their history attached.', 'for-realtors#lead-detail-title', 'users'],
    ['Tasks &amp; site visits', 'Due dates, priorities and completion notes, with visits linked to leads and inventory units.', 'for-realtors#follow-up', 'calendar'],
    ['Calls &amp; recordings', 'Call from the lead in the Android app, record outcomes, sync recordings to private storage, and upload on web only if a sync fails.', 'calls', 'phone'],
    ['Billing', 'Plan status, invoices as PDF, usage counters and an upgrade request inside the workspace.', 'pricing', 'file'],
    ['Account', 'Profile, password, notification preferences, theme, language and timezone.', 'for-realtors', 'sliders'],
];

$enterpriseFeatures = [
    ['Hierarchy &amp; roles', 'Admin, manager and employee roles with reporting lines, an org chart and backend-enforced scoping.', 'for-enterprises#roles', 'users'],
    ['Teams &amp; members', 'Create teams, assign managers, invite members, suspend or resume, transfer leads and hand over on exit.', 'for-enterprises#hierarchy-title', 'building'],
    ['Lead operations', 'Assignment, round-robin, bulk distribution, stage workflow, notes, deal value and inventory-unit linking.', 'for-enterprises#ops-title', 'layers'],
    ['Bulk import', 'CSV/XLSX/XLS up to 1,000 rows or 2 MB per batch, duplicate skip, monthly quota, team and campaign attribution.', 'for-enterprises#ops-title', 'upload'],
    ['Tasks &amp; visits', 'Assignment, due dates, visit outcomes and automatic follow-up tasks.', 'for-enterprises#ops-title', 'calendar'],
    ['Targets &amp; campaigns', 'Monthly targets for company, team and member; campaign attribution with server-side limits.', 'for-enterprises#ops-title', 'target'],
    ['Inventory', 'The same inventory hierarchy, with role-scoped availability transitions: admin any, manager team, employee own leads.', 'for-enterprises#roles', 'home'],
    ['Customers &amp; reports', 'Won deals, booked units, scoped reports and CSV export for admins and managers.', 'for-enterprises#ops-title', 'chart'],
    ['Enterprise Meta', 'Org-level page connection, form mapping, CAPI settings, manual sync and webhook ingestion where enabled.', 'for-enterprises#ops-title', 'sparkles'],
    ['Calls &amp; recordings', 'Employee sees own calls, manager sees the team subtree, admin sees all — with playback and outcomes.', 'calls', 'phone'],
    ['Governance', 'Branding, RERA number, working hours, audit log, seat add-on requests, invoices and plan limits.', 'for-enterprises#governance-title', 'shield'],
    ['Notifications', 'In-app bell notifications and admin broadcast messages.', 'for-enterprises', 'message'],
];

$page = [
    'title' => 'Features — Real Estate Lead, Call & Team Management | Rumu',
    'description' => 'The full Rumu capability map for realtors and enterprise builder teams: leads, Meta, calls, follow-ups, inventory, reports, roles and audit.',
    'canonical_path' => '/features',
    'nav' => 'features',
    'page_id' => 'features',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Features' => '/features'])],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Capability map</p>
            <h1 id="page-title">Every feature here ships today.</h1>
            <p class="lede">No roadmap theatre. Each card below maps to a module in the live web workspace. The Android app is clearly marked as a separate roadmap item.</p>
            <div class="button-row button-row--center">
                <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="features-start">Start free</a>
                <a class="btn btn-secondary btn-lg" href="<?= e(register_url('enterprise')) ?>" data-event="cta_click" data-cta="features-trial">Start enterprise trial</a>
            </div>
            <p class="hero-proof" style="justify-content:center">
                <span><?= icon('check') ?> Web panel live</span>
                <span><?= icon('check') ?> Android app in development</span>
                <span><?= icon('check') ?> Every claim traced to a shipped module</span>
            </p>
        </div>
    </section>

    <section class="section" aria-labelledby="features-title">
        <div class="container">
            <div class="section-head" id="realtor">
                <h2 id="features-title">Choose the edition whose features you need.</h2>
            </div>
            <div class="tabs" role="tablist" aria-label="Feature editions" data-tabs>
                <button type="button" class="tab" role="tab" id="tab-realtor" aria-selected="true" aria-controls="panel-realtor" data-tab-target="realtor">Realtor</button>
                <button type="button" class="tab" role="tab" id="tab-enterprise" aria-selected="false" aria-controls="panel-enterprise" data-tab-target="enterprise">Enterprise</button>
            </div>

            <div class="tab-panel" role="tabpanel" id="panel-realtor" aria-labelledby="tab-realtor" data-tab-panel="realtor">
                <p class="feature-count">12 shipped modules in the realtor workspace <span class="pill pill-success">Live</span></p>
                <div class="grid-3">
                    <?php foreach ($realtorFeatures as [$name, $benefit, $link, $iconName]): ?>
                        <article class="card feature-card">
                            <div class="card-icon"><?= icon($iconName) ?></div>
                            <h3><?= $name ?></h3>
                            <p><?= e($benefit) ?></p>
                            <a class="feature-link" href="<?= e(site_url($link)) ?>">See it in context <?= icon('arrow-right') ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-panel" role="tabpanel" id="panel-enterprise" aria-labelledby="tab-enterprise" data-tab-panel="enterprise" hidden>
                <p class="feature-count">12 shipped modules in the enterprise workspace <span class="pill pill-success">Live</span></p>
                <div class="grid-3">
                    <?php foreach ($enterpriseFeatures as [$name, $benefit, $link, $iconName]): ?>
                        <article class="card feature-card">
                            <div class="card-icon"><?= icon($iconName) ?></div>
                            <h3><?= $name ?></h3>
                            <p><?= e($benefit) ?></p>
                            <a class="feature-link" href="<?= e(site_url($link)) ?>">See it in context <?= icon('arrow-right') ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="honest-note" style="margin-top:30px">
                <?= icon('flag') ?>
                <p><strong>Not in the list on purpose:</strong> AI call summaries, transcription, Play Store availability, official WhatsApp Business API integration, ERP/accounting, and SOC 2 / ISO certification. They are not shipped, so they are not sold here.</p>
            </div>
        </div>
    </section>

    <?php
    $ctaTitle = 'See the workspace behind the feature list.';
    $ctaText = 'Start free, import a lead, and move it through the same board, timeline and calls the site describes.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'Start enterprise trial';
    $ctaSecondaryUrl = register_url('enterprise');
    $ctaId = 'features-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
