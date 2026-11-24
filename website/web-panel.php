<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/partials/ui-mockups.php';

$page = [
    'title' => 'The Rumu Web Panel — Dashboard, Lead Board, Calls & Inventory',
    'description' => 'See the Rumu web workspace: dashboard KPIs, a global lead board, lead timelines, private call recordings, inventory and reports. Live today.',
    'canonical_path' => '/web-panel',
    'nav' => 'features',
    'page_id' => 'web-panel',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Web panel' => '/web-panel'])],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Web panel · Live today</p>
            <h1 id="page-title">The cockpit.</h1>
            <p class="lede">Poora control, ek dashboard me. The web workspace is the product that ships today — no app install, same account, responsive down to a phone browser.</p>
            <div class="button-row button-row--center">
                <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="webpanel-hero-start">Start free</a>
                <a class="btn btn-secondary btn-lg" href="<?= e(login_url()) ?>" data-event="login_click">Open the workspace</a>
            </div>
            <p class="hero-proof" style="justify-content:center">
                <span><?= icon('check') ?> No install</span>
                <span><?= icon('check') ?> Works on phone browsers</span>
                <span><?= icon('check') ?> Same data when the app arrives</span>
            </p>
        </div>
    </section>

    <section class="section" id="dashboard" aria-labelledby="dashboard-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">01 · Dashboard</p>
                <h2 id="dashboard-title">The first screen answers “what needs me today?”</h2>
                <p>KPI counters, a conversion funnel, recent inquiries, campaign velocity and upcoming/overdue follow-ups — all on one screen.</p>
            </div>
            <div class="showcase-wrap">
                <?= ui_mockup('dashboard', ['caption' => 'Dashboard preview with sample data. The same screen ships in every workspace.', 'aria_label' => 'Rumu dashboard preview with KPIs, conversion funnel and upcoming follow-ups']) ?>
            </div>
            <div class="grid-3" style="margin-top:26px">
                <div class="card card--soft">
                    <h3>Today, not last month</h3>
                    <p class="small muted">New inquiries, follow-ups due, site visits and won deals sit above the fold.</p>
                </div>
                <div class="card card--soft">
                    <h3>Overdue is visible</h3>
                    <p class="small muted">Tasks, site visits and leads flag when the next step is late.</p>
                </div>
                <div class="card card--soft">
                    <h3>Campaign velocity</h3>
                    <p class="small muted">See which campaigns are producing movement, not just volume.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--soft" id="lead-board" aria-labelledby="board-title">
        <div class="container split split--wide">
            <div>
                <p class="kicker">02 · Lead board</p>
                <h2 id="board-title">Har lead ka ek owner aur ek stage.</h2>
                <p class="lede">The global lead board works across every campaign in kanban or table view. Move stages, search, filter and export — with CSV values made safe against formula injection.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Kanban and table views across all campaigns.</span></li>
                    <li><?= icon('check') ?><span>Search and filters for source, stage and owner.</span></li>
                    <li><?= icon('check') ?><span>Stage changes recorded in the lead status audit trail.</span></li>
                    <li><?= icon('check') ?><span>CSV export that blocks spreadsheet formula injection.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-secondary" href="<?= e(site_url('for-realtors')) ?>#workflow">See the lead workflow <?= icon('arrow-right', 'icon') ?></a>
                </div>
            </div>
            <div>
                <?= ui_mockup('lead-board', ['caption' => 'Lead board preview with sample data: New, Contacted, Site visit and Negotiation.', 'aria_label' => 'Rumu global lead board with four kanban columns and sample leads']) ?>
            </div>
        </div>
    </section>

    <section class="section" id="lead-detail" aria-labelledby="detail-title">
        <div class="container split split--wide">
            <div>
                <?= ui_mockup('lead-calls', ['caption' => 'Lead detail with the Calls tab selected: player, outcome and activity timeline.', 'aria_label' => 'Rumu lead detail screen with calls tab, recording player and activity timeline']) ?>
            </div>
            <div>
                <p class="kicker">03 · Lead detail</p>
                <h2 id="detail-title">Kaun, kab, kya bola — sab ek jagah.</h2>
                <p class="lede">The right panel carries a clickable stage rail; the centre holds activity, notes, follow-ups and the status audit trail. The contact rail keeps the source and campaign in view.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>WhatsApp / Note / Activity tiles, with calls on their own tab.</span></li>
                    <li><?= icon('check') ?><span>Convert a won lead to a customer in one action.</span></li>
                    <li><?= icon('check') ?><span>Soft delete, with the lead hidden from customers and reports.</span></li>
                    <li><?= icon('check') ?><span>Recordings attached to the lead with access logging.</span></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section--soft" id="calls" aria-labelledby="calls-title">
        <div class="container split split--wide">
            <div>
                <p class="kicker">04 · Calls</p>
                <h2 id="calls-title">Recording ho, toh baat yaad rehti hai.</h2>
                <p class="lede">A call list with outcomes, durations and playback. Recordings synced from the Android app are stored privately; the browser never receives a raw storage path.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Outcome tags and call notes on the lead.</span></li>
                    <li><?= icon('check') ?><span>Authenticated playback with an access-audit row.</span></li>
                    <li><?= icon('check') ?><span>Web upload fallback when an app sync fails.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-secondary" href="<?= e(site_url('calls')) ?>">Calls &amp; recordings <?= icon('arrow-right', 'icon') ?></a>
                </div>
            </div>
            <div>
                <?= ui_mockup('calls', ['caption' => 'Calls page preview with sample data. Stats, outcomes and the player.', 'aria_label' => 'Rumu calls page with call statistics, call list and a recording player']) ?>
            </div>
        </div>
    </section>

    <section class="section" id="inventory" aria-labelledby="inventory-title">
        <div class="container split split--wide">
            <div>
                <?= ui_mockup('inventory', ['caption' => 'Inventory preview with sample data: project → tower → wing → floor → unit.', 'aria_label' => 'Rumu inventory screen with project tree, unit grid and availability states']) ?>
            </div>
            <div>
                <p class="kicker">05 · Inventory</p>
                <h2 id="inventory-title">Project se unit tak availability.</h2>
                <p class="lede">A hierarchy that matches how you actually sell: Project → Tower → Wing → Floor → Unit, with Available, On hold, Booked and Sold states.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Filters by BHK, price, floor and area.</span></li>
                    <li><?= icon('check') ?><span>Site visits linked to leads and inventory units.</span></li>
                    <li><?= icon('check') ?><span>Role-scoped status changes in the enterprise edition.</span></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section--soft" id="reports" aria-labelledby="reports-title">
        <div class="container split split--wide">
            <div>
                <p class="kicker">06 · Reports &amp; targets</p>
                <h2 id="reports-title">Target vs actual, bina Excel.</h2>
                <p class="lede">Enterprise targets can be set for the company, a team or a member. Scoped reports and CSV export are available to admins and managers, with advanced reports on plans where the flag is enabled.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Target vs actual by team and month.</span></li>
                    <li><?= icon('check') ?><span>Won deals and booked units.</span></li>
                    <li><?= icon('check') ?><span>CSV export for permitted roles.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-secondary" href="<?= e(site_url('for-enterprises')) ?>#ops-title">Enterprise lead operations <?= icon('arrow-right', 'icon') ?></a>
                </div>
            </div>
            <div>
                <?= ui_mockup('reports', ['caption' => 'Enterprise reports preview with sample data: targets and manager scorecard.', 'aria_label' => 'Rumu enterprise reports screen with target vs actual and manager scorecard']) ?>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="limits-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Read this before you buy</p>
                <h2 id="limits-title">What the web panel is — and is not.</h2>
            </div>
            <div class="grid-2">
                <div class="card">
                    <h3>What it is</h3>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>A live web workspace for leads, calls, follow-ups, inventory and reports.</span></li>
                        <li><?= icon('check') ?><span>Responsive enough to use on a phone browser today.</span></li>
                        <li><?= icon('check') ?><span>The same account and data the planned Android app will use.</span></li>
                    </ul>
                </div>
                <div class="card">
                    <h3>What it is not</h3>
                    <ul class="mini-list">
                        <li><?= icon('x') ?><span>Not a native Android app — that is on the roadmap.</span></li>
                        <li><?= icon('x') ?><span>Not an AI transcription or call-summary tool — not built.</span></li>
                        <li><?= icon('x') ?><span>Not an ERP or accounting system — it runs the sales side.</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php
    $ctaTitle = 'Open the same workspace this page describes.';
    $ctaText = 'Create a free workspace, import a lead and move it across the board.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'See pricing';
    $ctaSecondaryUrl = site_url('pricing');
    $ctaId = 'webpanel-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
