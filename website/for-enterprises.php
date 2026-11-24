<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/partials/ui-mockups.php';

$pricing = fetch_plans();
$enterprisePlans = $pricing['plans']['enterprise'];

$faqs = [
    ['q' => 'Will the sales team actually adopt it?', 'a' => 'The interface is role-aware: an employee sees their own leads, a manager sees their team subtree, and an admin sees the organisation. Nobody is buried in data they do not need, and the workflow follows stages the team already uses.'],
    ['q' => 'How is data kept inside our organisation?', 'a' => 'Enterprise leads, calls and inventory are filtered server-side by enterprise ID, with membership and role checks enforced by the API. Cross-tenant access is rejected. Recordings are private and every playback writes an audit row.'],
    ['q' => 'Can we import our existing lead list?', 'a' => 'Yes. CSV, XLSX and XLS imports support up to 1,000 rows and 2 MB per batch, with duplicate email and phone rows skipped. Monthly lead quotas are enforced, and imports can carry team and campaign attribution.'],
    ['q' => 'Do you replace our ERP?', 'a' => 'No. Rumu runs the sales side — leads, follow-ups, visits, calls, inventory availability and team targets. It complements an ERP or accounting system rather than replacing it.'],
    ['q' => 'How do targets and reports work?', 'a' => 'Monthly targets can be set for the company, a team or a member. Scoped reports and CSV export are available to admins and managers, with advanced reports on plans where the flag is enabled.'],
    ['q' => 'Can we control seat changes and invoices?', 'a' => 'Admins can request seat add-ons and plan changes from workspace billing. Invoices are available as PDF downloads, and administrative actions are recorded in the audit log.'],
];

$page = [
    'title' => 'Enterprise Real Estate CRM — Team Visibility, Targets & Reports | Rumu',
    'description' => 'Rumu for builders and agencies: role-based visibility, lead distribution, targets, audit-ready activity and private call recordings. Start a 14-day trial.',
    'canonical_path' => '/for-enterprises',
    'nav' => 'enterprises',
    'page_id' => 'for-enterprises',
    'json_ld' => [
        breadcrumb_schema(['Home' => '/', 'For Enterprises' => '/for-enterprises']),
        faq_schema($faqs),
    ],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero" aria-labelledby="page-title">
        <div class="container">
            <div>
                <p class="kicker">Enterprise Edition</p>
                <h1 id="page-title">See every lead your team is working. And every one they’re not.</h1>
                <p class="lede">Role-based visibility, team distribution, targets, and audit-ready activity across every project. Admin, manager, employee — har level ko apna view.</p>
                <div class="button-row">
                    <a class="btn btn-primary btn-lg" href="<?= e(register_url('enterprise')) ?>" data-event="cta_click" data-cta="enterprise-hero-trial">Start 14-day trial</a>
                    <a class="btn btn-secondary btn-lg" href="#roles">See how it works</a>
                </div>
                <p class="hero-proof">
                    <span><?= icon('check') ?> Admin / manager / employee</span>
                    <span><?= icon('check') ?> Tenant-scoped data</span>
                    <span><?= icon('check') ?> Audit-ready history</span>
                </p>
            </div>
            <div class="page-hero-card">
                <h2>Board-ready visibility</h2>
                <div class="proof-strip">
                    <div><strong>3 roles</strong><span>Enforced by the API</span></div>
                    <div><strong>14 days</strong><span>To evaluate with real leads</span></div>
                    <div><strong>1 login</strong><span>Same account, every role</span></div>
                </div>
                <ul class="mini-list" style="margin-top:18px">
                    <li><?= icon('check') ?><span>Organisation → teams → members hierarchy</span></li>
                    <li><?= icon('check') ?><span>Distribution, targets and scoped reports</span></li>
                    <li><?= icon('check') ?><span>Private recordings with access audit</span></li>
                    <li><?= icon('check') ?><span>Org-level Meta connection and CAPI settings</span></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section" id="roles" aria-labelledby="roles-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Three buyers, one page</p>
                <h2 id="roles-title">Three views. One source of truth.</h2>
                <p>The emotional core is simple: “Main office me baitha hoon, lekin mujhe pata hi nahi kaun kaam kar raha hai.” Rumu sells visibility and control, not more features.</p>
            </div>
            <div class="role-grid">
                <article class="role-card">
                    <div class="card-icon"><?= icon('building') ?></div>
                    <h3>Owner / Director</h3>
                    <p>Revenue per project, ad spend ROI, and whether teams are actually working.</p>
                    <ul>
                        <li><?= icon('check') ?><span>Company, team and member targets</span></li>
                        <li><?= icon('check') ?><span>Project and campaign attribution</span></li>
                        <li><?= icon('check') ?><span>Scoped reports and CSV export</span></li>
                        <li><?= icon('check') ?><span>Audit log for administrative actions</span></li>
                    </ul>
                </article>
                <article class="role-card">
                    <div class="card-icon"><?= icon('users') ?></div>
                    <h3>Sales head / Manager</h3>
                    <p>Which team is behind, which rep is idle, and where follow-up discipline is slipping.</p>
                    <ul>
                        <li><?= icon('check') ?><span>Team subtree visibility</span></li>
                        <li><?= icon('check') ?><span>Round-robin and bulk distribution</span></li>
                        <li><?= icon('check') ?><span>Team targets vs actual</span></li>
                        <li><?= icon('check') ?><span>Call recordings with access history</span></li>
                    </ul>
                </article>
                <article class="role-card">
                    <div class="card-icon"><?= icon('sliders') ?></div>
                    <h3>Admin / Ops</h3>
                    <p>Data hygiene, access control, reporting and an audit trail that survives staff exits.</p>
                    <ul>
                        <li><?= icon('check') ?><span>Members, invitations, suspend and resume</span></li>
                        <li><?= icon('check') ?><span>Lead hand-over when a member leaves</span></li>
                        <li><?= icon('check') ?><span>Tenant-scoped queries and role middleware</span></li>
                        <li><?= icon('check') ?><span>Branding, RERA number and working hours</span></li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="hierarchy-title">
        <div class="container split split--wide">
            <div>
                <p class="kicker">Hierarchy &amp; roles</p>
                <h2 id="hierarchy-title">The org chart is the permission model.</h2>
                <p class="lede">Admin sees the organisation, a manager sees their team subtree, an employee sees their own leads. The interface hides what the API refuses.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Reporting lines and role-based data scoping.</span></li>
                    <li><?= icon('check') ?><span>Teams with assigned managers and member invitations.</span></li>
                    <li><?= icon('check') ?><span>Suspend or remove a member with lead hand-over.</span></li>
                    <li><?= icon('check') ?><span>Bulk import up to 1,000 rows / 2 MB with duplicate skip.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-secondary" href="<?= e(site_url('security')) ?>">How access control works <?= icon('arrow-right', 'icon') ?></a>
                </div>
            </div>
            <div>
                <?= ui_mockup('org', ['caption' => 'Hierarchy preview with sample data. Roles and reporting lines determine what each level can see.', 'aria_label' => 'Rumu enterprise hierarchy screen showing admin, managers and employees']) ?>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="ops-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Lead operations</p>
                <h2 id="ops-title">Distribution, targets and reports without the Excel evening.</h2>
                <p>Assign and distribute leads, set monthly targets, and review team performance from one dashboard.</p>
            </div>
            <div class="grid-2">
                <article class="card feature-card">
                    <div class="card-icon"><?= icon('layers') ?></div>
                    <h3>Bulk distribution</h3>
                    <p>Distribute unassigned leads to a team round-robin or to one owner, with team and campaign attribution kept intact. Import supports up to 1,000 rows per batch.</p>
                    <ul class="check-list" style="margin-top:6px">
                        <li><?= icon('check') ?><span>Round-robin or one-owner assignment</span></li>
                        <li><?= icon('check') ?><span>Duplicate emails and phones skipped</span></li>
                        <li><?= icon('check') ?><span>Monthly lead quota enforced</span></li>
                    </ul>
                </article>
                <article class="card feature-card">
                    <div class="card-icon"><?= icon('target') ?></div>
                    <h3>Targets and scorecards</h3>
                    <p>Set monthly targets for the company, a team or a member. Compare target vs actual, deals won and follow-up discipline in one place.</p>
                    <ul class="check-list" style="margin-top:6px">
                        <li><?= icon('check') ?><span>Company / team / member targets</span></li>
                        <li><?= icon('check') ?><span>Deal value and booked units</span></li>
                        <li><?= icon('check') ?><span>CSV export for admins and managers</span></li>
                    </ul>
                </article>
            </div>
            <div class="split" style="margin-top:34px">
                <div><?= ui_mockup('reports', ['caption' => 'Reports preview with sample data. Target vs actual, manager scorecards and export.', 'aria_label' => 'Rumu enterprise reports screen with targets and manager scorecards']) ?></div>
                <div>
                    <p class="kicker">Calls &amp; governance</p>
                    <h2 style="font-size:1.6rem">Every call has an outcome. Every playback has a log.</h2>
                    <p>Employees see their own calls, managers see the team subtree, admins see everything. Recordings are stored privately and streamed only to authorised users.</p>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>Outcome tags and call notes on the lead.</span></li>
                        <li><?= icon('check') ?><span>Private storage, authenticated playback, access audit.</span></li>
                        <li><?= icon('check') ?><span>Web upload fallback when an app sync fails.</span></li>
                        <li><?= icon('check') ?><span>Consent confirmed in the app before a call is logged.</span></li>
                    </ul>
                    <div class="button-row">
                        <a class="btn btn-secondary" href="<?= e(site_url('calls')) ?>">Calls &amp; recordings <?= icon('arrow-right', 'icon') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="governance-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Governance</p>
                <h2 id="governance-title">Control that survives a staff exit.</h2>
                <p>Access is enforced in the API, not hidden in the UI. Administrative actions leave a trail.</p>
            </div>
            <div class="governance-strip">
                <div class="governance-item">
                    <div class="card-icon card-icon--ink"><?= icon('users') ?></div>
                    <h3>Role middleware</h3>
                    <p>Membership, role and reporting hierarchy are checked before data is returned.</p>
                </div>
                <div class="governance-item">
                    <div class="card-icon card-icon--ink"><?= icon('clipboard') ?></div>
                    <h3>Audit log</h3>
                    <p>Member and administrative actions are recorded with actor and timestamp.</p>
                </div>
                <div class="governance-item">
                    <div class="card-icon card-icon--ink"><?= icon('lock') ?></div>
                    <h3>Private recordings</h3>
                    <p>Storage paths never reach the browser; playback logs an access row.</p>
                </div>
                <div class="governance-item">
                    <div class="card-icon card-icon--ink"><?= icon('shield') ?></div>
                    <h3>Tenant isolation</h3>
                    <p>Enterprise data is filtered server-side by enterprise ID; cross-tenant access is rejected.</p>
                </div>
            </div>
            <div class="callout" style="margin-top:24px">
                <p><strong>Procurement note:</strong> invoices are available as PDF downloads, seat add-ons and plan changes are requested from workspace billing, and administrative actions are logged. We do not claim SOC 2, ISO 27001 or any certification we do not hold.</p>
            </div>
        </div>
    </section>

    <section class="section" id="plans" aria-labelledby="plans-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Enterprise plans</p>
                <h2 id="plans-title">Limits you can plan a sales floor around.</h2>
                <p>Enterprise accounts start with a 14-day trial. Plan changes and seat additions go through workspace billing.</p>
            </div>
            <?php if ($pricing['ok'] && $enterprisePlans !== []): ?>
                <p class="table-hint">Swipe sideways to compare →</p>
                <div class="table-wrap" role="region" aria-label="Enterprise plan limits, scroll horizontally" tabindex="0">
                    <table class="compare">
                        <thead>
                            <tr>
                                <th scope="col">Limit</th>
                                <?php foreach ($enterprisePlans as $plan): ?>
                                    <th scope="col" class="<?= is_enterprise_plan($plan) ? 'is-rumu' : '' ?>">
                                        <?= e($plan['name']) ?>
                                        <small><?= e(plan_price($plan, $pricing['currency'])) ?><?= (float) $plan['price'] > 0 ? ' / month' : '' ?></small>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Managers</td><?php foreach ($enterprisePlans as $plan): ?><td><?= e(plan_limit($plan['max_managers'] ?? null)) ?></td><?php endforeach; ?></tr>
                            <tr><td>Employees</td><?php foreach ($enterprisePlans as $plan): ?><td><?= e(plan_limit($plan['max_employees'] ?? null)) ?></td><?php endforeach; ?></tr>
                            <tr><td>Teams</td><?php foreach ($enterprisePlans as $plan): ?><td><?= e(plan_limit($plan['max_teams'] ?? null)) ?></td><?php endforeach; ?></tr>
                            <tr><td>Campaigns</td><?php foreach ($enterprisePlans as $plan): ?><td><?= e(plan_limit($plan['max_campaigns'] ?? null)) ?></td><?php endforeach; ?></tr>
                            <tr><td>Monthly leads</td><?php foreach ($enterprisePlans as $plan): ?><td><?= e(plan_limit($plan['max_monthly_leads'] ?? null)) ?></td><?php endforeach; ?></tr>
                            <tr><td>Inventory units</td><?php foreach ($enterprisePlans as $plan): ?><td><?= e(plan_limit($plan['max_inventory_units'] ?? null)) ?></td><?php endforeach; ?></tr>
                            <tr><td>Advanced reports</td><?php foreach ($enterprisePlans as $plan): ?><td><?= !empty($plan['advanced_reports']) ? '<span class="yes">Included</span>' : '<span class="no">Not included</span>' ?></td><?php endforeach; ?></tr>
                            <tr><td>Enterprise Meta integrations</td><?php foreach ($enterprisePlans as $plan): ?><td><?= !empty($plan['meta_integrations']) ? '<span class="yes">Included</span>' : '<span class="no">Not included</span>' ?></td><?php endforeach; ?></tr>
                        </tbody>
                    </table>
                </div>
                <?php
                $taxExtra = !empty($pricing['tax']['enabled']) && empty($pricing['tax']['inclusive']);
                ?>
                <p class="pricing-tax-note">Prices in <?= e($pricing['currency']) ?><?= $taxExtra ? ' · GST added where applicable' : '' ?>. Trial terms are applied during enterprise registration.</p>
                <div class="button-row button-row--center" style="margin-top:24px">
                    <a class="btn btn-primary btn-lg" href="<?= e(register_url('enterprise')) ?>" data-event="cta_click" data-cta="enterprise-plans-trial">Start 14-day trial</a>
                    <a class="btn btn-secondary btn-lg" href="<?= e(site_url('pricing')) ?>">Compare all plans</a>
                </div>
            <?php else: ?>
                <div class="pricing-unavailable" role="status">
                    <div class="pricing-unavailable-icon"><?= icon('sliders') ?></div>
                    <div>
                        <h3>Live plan limits are temporarily unavailable</h3>
                        <p>We do not print stale limits. Open the enterprise registration flow to see the current plans and quotas.</p>
                        <div class="button-row">
                            <a class="btn btn-primary" href="<?= e(register_url('enterprise')) ?>">Start 14-day trial</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="faq-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Procurement FAQ</p>
                <h2 id="faq-title">The questions before a rollout.</h2>
            </div>
            <div class="faq-list">
                <?php foreach ($faqs as $faq): ?>
                    <details class="faq-item">
                        <summary><?= e($faq['q']) ?><?= icon('chevron-down') ?></summary>
                        <p><?= e($faq['a']) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php
    $ctaTitle = 'Start on the free trial. Decide with your own leads.';
    $ctaText = 'Import a real batch, invite a manager, and see the team view before you commit to a rollout.';
    $ctaPrimaryLabel = 'Start 14-day trial';
    $ctaPrimaryUrl = register_url('enterprise');
    $ctaSecondaryLabel = 'See plan limits';
    $ctaSecondaryUrl = site_url('pricing') . '?edition=enterprise';
    $ctaNote = '14-day trial · Role-scoped data · Invoices and seat requests in billing';
    $ctaId = 'enterprise-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
