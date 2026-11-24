<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/partials/ui-mockups.php';

$pricing = fetch_plans();
$realtorPlans = $pricing['plans']['realtor'];
$enterprisePlans = $pricing['plans']['enterprise'];

$faqs = [
    [
        'q' => 'Is my lead data private to my workspace?',
        'a' => 'Yes. Realtor workspaces and enterprise tenants are scoped by the API, and the interface only shows what your role is allowed to access. Leads are not shared across accounts. You can export your leads as CSV whenever you need them.',
    ],
    [
        'q' => 'Do Meta Lead Ads come into Rumu automatically?',
        'a' => 'You can import a Meta Lead Ads CSV, or connect the Meta integration and receive leads in real time. Meta CAPI sends server-side conversion events back to Meta when lead stages change. The Meta integration is available on plans where the feature is enabled.',
    ],
    [
        'q' => 'How do call recordings work?',
        'a' => 'Calls start in the Rumu Android app (in development), on your own SIM. After the call, the app logs the outcome and uploads the handset recording to private workspace storage. The recording plays only for authorised users, and every access is logged. If the app sync fails, you can upload the recording on web. Recording consent is the customer’s responsibility and the app asks for confirmation before logging a call.',
    ],
    [
        'q' => 'Is there an Android app?',
        'a' => 'Not yet. The Android app is in development. Everything on this site works today in the responsive web workspace, with the same account and data, so there will be no migration later.',
    ],
    [
        'q' => 'Can I move from Excel or another CRM?',
        'a' => 'Yes. Import leads from CSV, XLSX or XLS, or paste them manually. Duplicate emails and phones are skipped during enterprise bulk imports. Start with the free plan and decide with your own data.',
    ],
    [
        'q' => 'What happens on the free plan or trial?',
        'a' => 'Basic is free with 5 campaigns and 100 leads per campaign. Enterprise accounts start with a 14-day trial. Plan changes and seat additions go through workspace billing, and your data stays exportable.',
    ],
];

$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(static fn ($faq) => [
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
    ], $faqs),
];

$offers = [];
if ($pricing['ok']) {
    foreach (array_merge($realtorPlans, $enterprisePlans) as $plan) {
        $offers[] = [
            '@type' => 'Offer',
            'name' => $plan['name'],
            'price' => (float) $plan['price'],
            'priceCurrency' => $pricing['currency'],
            'url' => plan_register_url($plan),
        ];
    }
}
$appSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => 'Rumu',
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'description' => 'Real-estate sales workspace for Indian realtors and enterprise builder teams. Leads, calls, follow-ups, inventory and team visibility in one place.',
    'url' => config('site_url'),
    'offers' => $offers,
];

$page = [
    'title' => 'Rumu — Real Estate CRM for Realtors & Builders | Leads, Calls, Follow-ups',
    'description' => 'Rumu turns property enquiries into site visits. Leads, calls, follow-ups, inventory and team visibility in one real-estate sales workspace. Start free.',
    'canonical_path' => '/',
    'nav' => 'home',
    'page_id' => 'home',
    'json_ld' => [$appSchema, $faqSchema],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="hero" aria-labelledby="hero-title">
        <div class="container hero-grid">
            <div class="hero-copy">
                <p class="kicker">Built for Indian real-estate teams</p>
                <h1 id="hero-title">Every lead. Every call.<br> <span class="accent">Every follow-up. In one place.</span></h1>

                <div class="edition-toggle" role="tablist" aria-label="Choose your edition">
                    <button type="button" role="tab" id="edition-tab-realtor" aria-selected="true" aria-controls="edition-realtor" data-edition-btn="realtor">For Realtors</button>
                    <button type="button" role="tab" id="edition-tab-enterprise" aria-selected="false" aria-controls="edition-enterprise" data-edition-btn="enterprise">For Enterprises</button>
                </div>

                <div class="edition-panel" data-edition-panel="realtor" id="edition-realtor" role="tabpanel" aria-labelledby="edition-tab-realtor">
                    <p class="hero-sub">Rumu turns property enquiries into site visits — without spreadsheets, WhatsApp chaos, or missed callbacks.</p>
                    <div class="button-row">
                        <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="hero-realtor-start">Start free</a>
                        <a class="btn btn-secondary btn-lg" href="<?= e(login_url()) ?>" data-event="login_click">Log in</a>
                    </div>
                    <p class="hero-proof">
                        <span><?= icon('check') ?> Free plan</span>
                        <span><?= icon('check') ?> No credit card</span>
                        <span><?= icon('check') ?> Your data stays yours</span>
                    </p>
                </div>

                <div class="edition-panel" data-edition-panel="enterprise" id="edition-enterprise" role="tabpanel" aria-labelledby="edition-tab-enterprise" hidden>
                    <p class="hero-sub">Role-based visibility, team distribution, targets, and audit-ready activity across every project.</p>
                    <div class="button-row">
                        <a class="btn btn-primary btn-lg" href="<?= e(register_url('enterprise')) ?>" data-event="cta_click" data-cta="hero-enterprise-trial">Start 14-day trial</a>
                        <a class="btn btn-secondary btn-lg" href="<?= e(site_url('for-enterprises')) ?>">See how it works</a>
                    </div>
                    <p class="hero-proof">
                        <span><?= icon('check') ?> 14-day trial</span>
                        <span><?= icon('check') ?> Role-scoped data</span>
                        <span><?= icon('check') ?> Audit-ready history</span>
                    </p>
                </div>
            </div>

            <div class="hero-visual">
                <span class="hero-badge"><span class="dot" aria-hidden="true"></span> Web panel live today</span>
                <div class="edition-panel" data-edition-panel="realtor">
                    <?= ui_mockup('dashboard', ['caption' => 'Realtor dashboard with sample data. KPIs, funnel and today’s follow-ups in one view.', 'aria_label' => 'Rumu realtor dashboard showing KPI counters, conversion funnel and upcoming follow-ups']) ?>
                </div>
                <div class="edition-panel" data-edition-panel="enterprise" hidden>
                    <?= ui_mockup('enterprise-dashboard', ['caption' => 'Enterprise dashboard with sample data. Team performance, unassigned leads and overdue follow-ups.', 'aria_label' => 'Rumu enterprise dashboard showing team performance and unassigned leads']) ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="problem-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">The three leaks</p>
                <h2 id="problem-title">It is rarely a lead problem. It is a follow-up problem.</h2>
                <p>Most real-estate teams do not lose deals because enquiries are scarce. They lose them because the next step lives in someone’s head.</p>
            </div>
            <div class="grid-3 problem-grid">
                <article class="card problem-card">
                    <div class="card-icon"><?= icon('message') ?></div>
                    <h3>Leads WhatsApp me kho jaate hain</h3>
                    <p>A Meta form at 11 PM, a portal enquiry at 7 AM, an Excel from a channel partner. The follow-up lives in three different places.</p>
                    <span class="quote">“Lead chhoote na.”</span>
                </article>
                <article class="card problem-card">
                    <div class="card-icon"><?= icon('calendar') ?></div>
                    <h3>Follow-up yaad nahi rehta</h3>
                    <p>The buyer says “call me after 6”. By 6, another agent has already called. Nobody can prove who followed up, or when.</p>
                    <span class="quote">“Ek bhoola hua follow-up = ek lost deal.”</span>
                </article>
                <article class="card problem-card">
                    <div class="card-icon"><?= icon('eye') ?></div>
                    <h3>Team ka kaam dikhta nahi</h3>
                    <p>In the office it is hard to see who is touching which lead, which call happened, and which team’s number is behind.</p>
                    <span class="quote">“Visibility, not more features.”</span>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="outcome-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">What changes</p>
                <h2 id="outcome-title">One workspace. One owner. One history.</h2>
                <p>No invented percentages. These are the three things the product actually gives you.</p>
            </div>
            <div class="outcome-strip">
                <div class="outcome-item">
                    <p class="kicker">One workspace</p>
                    <h3>Every source, one pipeline</h3>
                    <p>Meta Lead Ads, manual entry, CSV/XLSX import and portal lists land in the same lead board.</p>
                </div>
                <div class="outcome-item">
                    <p class="kicker">One owner</p>
                    <h3>Every lead has a next step</h3>
                    <p>Stage, owner, follow-up date and activity history stay attached to the lead — not a personal chat.</p>
                </div>
                <div class="outcome-item">
                    <p class="kicker">One history</p>
                    <h3>Every call leaves proof</h3>
                    <p>Outcome, notes and recordings sit on the lead so the next person does not start from zero.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="showcase" aria-labelledby="showcase-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">The web panel</p>
                <h2 id="showcase-title">The cockpit for your sales day.</h2>
                <p>The web workspace is the product that ships today. Live KPIs, a global lead board, calls with playback, and inventory that matches your project structure.</p>
            </div>

            <div class="showcase-wrap">
                <div class="hotspot hotspot--1" aria-hidden="true"><span class="hotspot-dot">1</span><span class="hotspot-label">Dashboard KPIs</span></div>
                <div class="hotspot hotspot--2" aria-hidden="true"><span class="hotspot-dot">2</span><span class="hotspot-label">Follow-ups</span></div>
                <div class="hotspot hotspot--3" aria-hidden="true"><span class="hotspot-dot">3</span><span class="hotspot-label">Lead board</span></div>
                <div class="hotspot hotspot--4" aria-hidden="true"><span class="hotspot-dot">4</span><span class="hotspot-label">Calls &amp; inventory</span></div>
                <?= ui_mockup('dashboard', ['caption' => 'Realtor dashboard preview with sample data. KPIs, funnel and today\'s follow-ups in one view.', 'aria_label' => 'Annotated Rumu dashboard preview showing KPIs, conversion funnel and follow-ups']) ?>
            </div>

            <div class="showcase-callouts">
                <a class="showcase-callout" href="<?= e(site_url('web-panel')) ?>#dashboard">
                    <span class="num">1</span>
                    <span><strong>Dashboard</strong><span>KPIs, funnel, recent inquiries, campaign velocity and overdue follow-ups.</span></span>
                </a>
                <a class="showcase-callout" href="<?= e(site_url('web-panel')) ?>#lead-board">
                    <span class="num">2</span>
                    <span><strong>Lead board</strong><span>Kanban and table across campaigns, with search, filters and CSV export.</span></span>
                </a>
                <a class="showcase-callout" href="<?= e(site_url('calls')) ?>">
                    <span class="num">3</span>
                    <span><strong>Calls</strong><span>App-synced recordings with outcome, playback and access audit; web upload if a sync fails.</span></span>
                </a>
                <a class="showcase-callout" href="<?= e(site_url('web-panel')) ?>#inventory">
                    <span class="num">4</span>
                    <span><strong>Inventory</strong><span>Project → tower → wing → floor → unit, with availability states and filters.</span></span>
                </a>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="editions-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Two editions, one account system</p>
                <h2 id="editions-title">Start alone. Scale to a sales floor.</h2>
                <p>Enterprise is additive: the realtor workspace stays intact, with hierarchy and control layered on top.</p>
            </div>
            <div class="edition-grid">
                <article class="edition-card">
                    <p class="edition-kicker">Realtor Edition</p>
                    <h2>Your whole business, in your pocket.</h2>
                    <p>For individual agents, freelancers and small brokerages. One user, one workspace, every lead accounted for.</p>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>Lead board, follow-ups, tasks and site visits</span></li>
                        <li><?= icon('check') ?><span>Meta Lead Ads import and real-time sync</span></li>
                        <li><?= icon('check') ?><span>Calls, outcomes and app-synced recordings</span></li>
                        <li><?= icon('check') ?><span>Project → unit inventory with availability</span></li>
                        <li><?= icon('check') ?><span>Free Basic plan, Pro at the price shown in the app</span></li>
                    </ul>
                    <div class="button-row">
                        <a class="btn btn-primary" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="editions-realtor">Start free</a>
                        <a class="btn btn-ghost" href="<?= e(site_url('for-realtors')) ?>">Explore realtor edition <?= icon('arrow-right', 'icon') ?></a>
                    </div>
                </article>
                <article class="edition-card edition-card--enterprise">
                    <p class="edition-kicker">Enterprise Edition</p>
                    <h2>See every lead your team is working. And every one they’re not.</h2>
                    <p>For builders, developers and agencies with a sales team. Organisation → teams → members, with hierarchy-scoped data.</p>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>Admin / manager / employee roles and reporting lines</span></li>
                        <li><?= icon('check') ?><span>Lead distribution, round-robin and bulk assignment</span></li>
                        <li><?= icon('check') ?><span>Targets, scoped reports and CSV export</span></li>
                        <li><?= icon('check') ?><span>Audit log and private recordings with access history</span></li>
                        <li><?= icon('check') ?><span>Org-level Meta connection and CAPI settings</span></li>
                    </ul>
                    <div class="button-row">
                        <a class="btn btn-light" href="<?= e(register_url('enterprise')) ?>" data-event="cta_click" data-cta="editions-enterprise">Start 14-day trial</a>
                        <a class="btn btn-secondary" href="<?= e(site_url('for-enterprises')) ?>">Explore enterprise edition <?= icon('arrow-right', 'icon') ?></a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="calls-title">
        <div class="container split">
            <div>
                <p class="kicker">Calls &amp; recordings</p>
                <h2 id="calls-title">Calls that leave proof, not memory.</h2>
                <p class="lede">Call the lead from the Rumu Android app on your own SIM; the recording syncs back with the outcome and a private audit trail.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Outcome tags: connected, busy, wrong number, call back, interested, converted and more.</span></li>
                    <li><?= icon('check') ?><span>Playback is authenticated and every access writes an audit row.</span></li>
                    <li><?= icon('check') ?><span>App sync failed? Upload MP3, WAV, M4A, MP4, OGG, AMR, AAC, 3GP or WebM up to 50 MB on web.</span></li>
                </ul>
                <div class="honest-note" style="margin-top:22px">
                    <?= icon('phone') ?>
                    <p><strong>Honest limit:</strong> the Android app is in development and there is no call button on the web dashboard. Until the app ships, the web upload fallback keeps the history complete.</p>
                </div>
                <div class="button-row">
                    <a class="btn btn-secondary" href="<?= e(site_url('calls')) ?>">See how calls work <?= icon('arrow-right', 'icon') ?></a>
                </div>
            </div>
            <div>
                <?= ui_mockup('calls', ['caption' => 'Calls page preview with sample data. Outcomes, recordings and an authenticated player.', 'aria_label' => 'Rumu calls page preview with call list, outcomes and a recording player']) ?>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="app-title">
        <div class="container split">
            <div>
                <p class="kicker">Android app · Roadmap</p>
                <h2 id="app-title">Aapka desk, aapki jeb me. Coming soon.</h2>
                <p class="lede">The Android app is in development. The web workspace is live today — same account, same data, no migration later.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Planned: same account login and role-aware navigation.</span></li>
                    <li><?= icon('check') ?><span>Planned: lead updates, tasks, site visits and SIM calling from the lead screen.</span></li>
                    <li><?= icon('check') ?><span>Planned: automatic recording sync and notifications.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-primary" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="app-teaser-start">Start on web</a>
                    <a class="btn btn-secondary" href="<?= e(site_url('app')) ?>">See the roadmap</a>
                </div>
            </div>
            <div class="card card--soft" style="display:flex;flex-direction:column;align-items:center;justify-content:center">
                <span class="pill pill-warning" style="margin-bottom:18px">Coming soon</span>
                <?= ui_mockup('app-wire') ?>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="pricing-preview-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Pricing</p>
                <h2 id="pricing-preview-title">Start free. Upgrade when the pipeline pays for it.</h2>
                <p>Prices and limits are fetched live from the product. If pricing cannot load, we hide the numbers rather than show a stale one.</p>
            </div>
            <?php
            $plans = $realtorPlans;
            $currency = $pricing['currency'];
            $tax = $pricing['tax'];
            $compact = true;
            $audience = 'realtor';
            $pricingPrefix = 'home-plan';
            require __DIR__ . '/partials/pricing-cards.php';
            ?>
            <div class="button-row button-row--center" style="margin-top:28px">
                <a class="btn btn-secondary" href="<?= e(site_url('pricing')) ?>" data-event="cta_click" data-cta="home-see-pricing">See all plans &amp; limits</a>
                <a class="btn btn-ghost" href="<?= e(site_url('for-enterprises')) ?>">Enterprise plans start with a 14-day trial <?= icon('arrow-right', 'icon') ?></a>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="faq-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Straight answers</p>
                <h2 id="faq-title">Questions buyers actually ask.</h2>
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
    $ctaTitle = 'Make the next follow-up impossible to forget.';
    $ctaText = 'Open a workspace, import your leads and see the whole day in one screen. Free to start.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'Start enterprise trial';
    $ctaSecondaryUrl = register_url('enterprise');
    $ctaNote = 'Free plan · No credit card · Your data stays yours';
    $ctaId = 'home-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
