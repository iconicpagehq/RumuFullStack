<?php
require_once __DIR__ . '/lib/helpers.php';

$page = [
    'title' => 'Security & Access Control — Private Leads and Recordings | Rumu',
    'description' => 'How Rumu scopes leads and calls: token auth, role middleware, tenant isolation, private recordings with access audit, and formula-safe exports.',
    'canonical_path' => '/security',
    'nav' => 'features',
    'page_id' => 'security',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Security' => '/security'])],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Trust centre</p>
            <h1 id="page-title">Your leads are yours.</h1>
            <p class="lede">Aapka data, aapka control. This page describes how access actually works — roles, scoping, private recordings and audit — without certification badges we do not hold.</p>
            <div class="button-row button-row--center">
                <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="security-start">Start free</a>
                <a class="btn btn-secondary btn-lg" href="<?= e(site_url('contact')) ?>">Ask a security question</a>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="layers-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Access control</p>
                <h2 id="layers-title">Six layers between a person and the wrong data.</h2>
                <p>The interface hides what the API refuses. The API is the enforcement point.</p>
            </div>
            <div class="security-diagram">
                <div class="layer">
                    <div class="layer-icon"><?= icon('lock') ?></div>
                    <div><h3>Token authentication</h3><p>Laravel Sanctum bearer tokens, with per-device logout and logout-all.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('shield') ?></div>
                    <div><h3>Role middleware and policies</h3><p>Enterprise data is scoped by membership, role and reporting hierarchy before a query returns.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('layers') ?></div>
                    <div><h3>Tenant isolation</h3><p>Enterprise leads, calls and inventory are filtered server-side by enterprise ID; cross-tenant access is rejected.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('mic') ?></div>
                    <div><h3>Private recordings</h3><p>Recordings live on a private disk, stream only to authorised users, and are marked private/no-store. Storage paths never reach the browser.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('file') ?></div>
                    <div><h3>Formula-safe exports</h3><p>CSV values beginning with =, +, - or @ are prefixed so spreadsheet formula injection is blocked.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('clipboard') ?></div>
                    <div><h3>Audit trails</h3><p>Enterprise administrative and member actions, call access, and lead status changes leave a trail.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="matrix-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Role matrix</p>
                <h2 id="matrix-title">Who sees what, at a glance.</h2>
                <p>Enterprise roles are additive. The realtor workspace is a single-user scope.</p>
            </div>
            <p class="table-hint">Swipe sideways to compare →</p>
            <div class="table-wrap" role="region" aria-label="Role access matrix, scroll horizontally" tabindex="0">
                <table class="compare">
                    <thead>
                        <tr>
                            <th scope="col">Data area</th>
                            <th scope="col" class="is-rumu">Admin</th>
                            <th scope="col">Manager</th>
                            <th scope="col">Employee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Leads</td><td>Company-wide</td><td>Team subtree</td><td>Own leads</td></tr>
                        <tr><td>Calls &amp; recordings</td><td>All calls</td><td>Team subtree</td><td>Own calls</td></tr>
                        <tr><td>Inventory availability changes</td><td>Any unit</td><td>Team scope</td><td>Own leads</td></tr>
                        <tr><td>Reports &amp; export</td><td>Company reports</td><td>Team reports<small>Advanced reports where the plan flag is enabled</small></td><td>Not included</td></tr>
                        <tr><td>Teams &amp; hierarchy</td><td>Manage</td><td>View team</td><td>No access</td></tr>
                        <tr><td>Audit log</td><td>Yes</td><td>Scoped view where provided</td><td>No access</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="recording-title">
        <div class="container split split--wide">
            <div>
                <p class="kicker">Recordings</p>
                <h2 id="recording-title">Private by default, logged on every play.</h2>
                <p class="lede">The Android app uploads the call recording after the call ends. Rumu validates and stores the file privately, writes the call record, and streams the audio itself to authorised users.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Raw storage paths are never exposed to the browser.</span></li>
                    <li><?= icon('check') ?><span>Cache-Control private, no-store on playback responses.</span></li>
                    <li><?= icon('check') ?><span>Access-audit row on each recording access.</span></li>
                    <li><?= icon('check') ?><span>Consent confirmed in the app before a call is logged.</span></li>
                    <li><?= icon('check') ?><span>Web upload fallback when an app sync fails.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-secondary" href="<?= e(site_url('legal/recording')) ?>">Recording policy <?= icon('arrow-right', 'icon') ?></a>
                </div>
            </div>
            <div class="card">
                <h3>Data ownership</h3>
                <p>Your leads and recordings stay in your workspace. Leads are exportable as CSV, and recordings remain attached to the lead’s history. Deleting a lead soft-deletes it from customer and report views while preserving policy-scoped records where required.</p>
                <h3 style="margin-top:20px">Marketing site privacy</h3>
                <p>This site does not load advertising pixels. Interaction events are pushed to the page’s <code>dataLayer</code> and are not sent to a third-party analytics service by this build. Google Fonts is loaded from Google’s servers.</p>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="limits-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Honest limits</p>
                <h2 id="limits-title">No badges we did not earn.</h2>
            </div>
            <div class="grid-2">
                <div class="card">
                    <h3>What we can say</h3>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>Server-side role and tenant scoping.</span></li>
                        <li><?= icon('check') ?><span>Private recording storage and access audit.</span></li>
                        <li><?= icon('check') ?><span>Formula-injection-safe CSV exports.</span></li>
                        <li><?= icon('check') ?><span>Audit-friendly lead and admin activity history.</span></li>
                    </ul>
                </div>
                <div class="card">
                    <h3>What we do not claim</h3>
                    <ul class="mini-list">
                        <li><?= icon('x') ?><span>SOC 2, ISO 27001 or PCI certification — not audited.</span></li>
                        <li><?= icon('x') ?><span>“Bank-grade” or “military-grade” security language.</span></li>
                        <li><?= icon('x') ?><span>Regulatory compliance by Rumu; RERA number and working hours are stored in your workspace by you.</span></li>
                        <li><?= icon('x') ?><span>Automatic recording retention purge — not implemented yet.</span></li>
                    </ul>
                </div>
            </div>
            <div class="honest-note" style="margin-top:24px">
                <?= icon('shield') ?>
                <p><strong>Recording consent is the customer’s responsibility.</strong> Rumu provides the announcement switch and private access controls; it does not decide whether recording is lawful for your call.</p>
            </div>
        </div>
    </section>

    <?php
    $ctaTitle = 'Evaluate with your own data.';
    $ctaText = 'Start free, import a real batch, and look at the access model from inside a workspace.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'Start enterprise trial';
    $ctaSecondaryUrl = register_url('enterprise');
    $ctaNote = 'No certification claims · access enforced by the API';
    $ctaId = 'security-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
