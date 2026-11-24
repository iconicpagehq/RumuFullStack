<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/partials/ui-mockups.php';

$pricing = fetch_plans();
$realtorPlans = $pricing['plans']['realtor'];

$faqs = [
    ['q' => 'Can I start without paying?', 'a' => 'Yes. The Basic plan is free and includes 5 campaigns with up to 100 leads per campaign. No credit card is needed to create your account or import your first leads.'],
    ['q' => 'Does the web workspace work on my phone?', 'a' => 'Yes, the web panel is responsive and usable on a phone browser. The dedicated Android app is still in development and is listed as a roadmap item, not a shipped product.'],
    ['q' => 'How fast can I import my existing leads?', 'a' => 'You can paste a lead manually, import CSV/XLSX/XLS, or import a Meta Lead Ads export. Name, email, phone and company are supported, and sample templates are available inside the workspace.'],
    ['q' => 'Can I connect Meta Lead Ads?', 'a' => 'Yes. You can import Meta Lead Ads CSV files or connect the Meta integration where it is enabled. Meta CAPI can send server-side conversion events on lead stage changes.'],
    ['q' => 'What happens to my data if I stop?', 'a' => 'Your leads stay in your workspace and can be exported as CSV. Access limits apply based on your plan, but you are not locked in.'],
    ['q' => 'Do I need training?', 'a' => 'No training call is required. Signup is three steps, plans are selected inside the app, and the workspace is designed around the same stages a salesperson already uses.'],
];

$page = [
    'title' => 'CRM for Indian Realtors — Capture Leads, Track Calls, Close More | Rumu',
    'description' => 'Rumu for realtors: capture Meta, portal and Excel leads, plan follow-ups, manage inventory and keep every call. Start free with no credit card.',
    'canonical_path' => '/for-realtors',
    'nav' => 'realtors',
    'page_id' => 'for-realtors',
    'json_ld' => [
        breadcrumb_schema(['Home' => '/', 'For Realtors' => '/for-realtors']),
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
                <p class="kicker">Realtor Edition</p>
                <h1 id="page-title">Your whole business, in your pocket.</h1>
                <p class="lede">Capture leads, plan follow-ups, manage inventory, and close more — starting free. Built for the way Indian agents actually sell.</p>
                <div class="button-row">
                    <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="realtor-hero-start">Start free</a>
                    <a class="btn btn-secondary btn-lg" href="<?= e(site_url('pricing')) ?>">See pricing</a>
                </div>
                <p class="hero-proof">
                    <span><?= icon('check') ?> Free Basic plan</span>
                    <span><?= icon('check') ?> No credit card</span>
                    <span><?= icon('check') ?> Three-step signup</span>
                </p>
            </div>
            <div class="page-hero-card">
                <h2>What you get on day one</h2>
                <div class="proof-strip">
                    <div><strong>₹0</strong><span>Basic plan to start</span></div>
                    <div><strong>3 steps</strong><span>Signup to workspace</span></div>
                    <div><strong>1 board</strong><span>Every lead, every stage</span></div>
                </div>
                <ul class="mini-list" style="margin-top:18px">
                    <li><?= icon('check') ?><span>Manual, CSV/XLSX and Meta Lead Ads capture</span></li>
                    <li><?= icon('check') ?><span>Follow-ups, tasks and site visits</span></li>
                    <li><?= icon('check') ?><span>Inventory from project to unit</span></li>
                    <li><?= icon('check') ?><span>Calls, outcomes and app-synced recordings</span></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="day-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">A day in the life</p>
                <h2 id="day-title">The same day, with and without a system.</h2>
                <p>You do not need more leads. You need to stop losing the ones you already paid for.</p>
            </div>
            <div class="timeline-story">
                <article class="story-col story-col--bad">
                    <h2>Without Rumu</h2>
                    <ol>
                        <li>7 AM: check Meta leads, screenshot them into WhatsApp.</li>
                        <li>Call 12 people; 3 say “call later”. Two of those reminders never get written down.</li>
                        <li>A site visit gets double-booked because the calendar lives in your head.</li>
                        <li>Evening: try to remember which campaign gave the good lead, and which rep actually followed up.</li>
                    </ol>
                </article>
                <article class="story-col story-col--good">
                    <h2>With Rumu</h2>
                    <ol>
                        <li>7 AM: Meta, portal and Excel leads are already in one lead board.</li>
                        <li>Every “call later” becomes a follow-up with a date, an owner and a reminder.</li>
                        <li>Site visits are scheduled against the lead and the inventory unit.</li>
                        <li>Evening: the dashboard shows campaign-wise leads, calls and the next overdue follow-up.</li>
                    </ol>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="benefits-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Six things that change</p>
                <h2 id="benefits-title">Speed, follow-up, ad ROI, inventory, calls — and a free start.</h2>
            </div>
            <div class="grid-3">
                <article class="card feature-card" id="speed">
                    <div class="feature-meta"><span class="pill pill-success">Live</span></div>
                    <div class="card-icon"><?= icon('bolt') ?></div>
                    <h3>Start in minutes, not weeks</h3>
                    <p>Create an account, choose a plan inside the app, and import your first leads from a template. No onboarding project, no training call.</p>
                    <a class="feature-link" href="#workflow">See the workflow <?= icon('arrow-right') ?></a>
                </article>
                <article class="card feature-card" id="follow-up">
                    <div class="feature-meta"><span class="pill pill-success">Live</span></div>
                    <div class="card-icon"><?= icon('repeat') ?></div>
                    <h3>Next follow-up, never forgotten</h3>
                    <p>Tasks, follow-ups and site visits sit on the dashboard with due dates and overdue flags. Completion notes keep the context.</p>
                    <a class="feature-link" href="<?= e(site_url('features')) ?>#realtor">All follow-up tools <?= icon('arrow-right') ?></a>
                </article>
                <article class="card feature-card" id="meta">
                    <div class="feature-meta"><span class="pill pill-success">Live</span></div>
                    <div class="card-icon"><?= icon('target') ?></div>
                    <h3>Meta leads, better ad signal</h3>
                    <p>Import a Meta Lead Ads CSV or sync leads where the integration is enabled. CAPI sends server-side conversion events on stage changes.</p>
                    <a class="feature-link" href="<?= e(site_url('features')) ?>#realtor">Meta features <?= icon('arrow-right') ?></a>
                </article>
                <article class="card feature-card" id="inventory">
                    <div class="feature-meta"><span class="pill pill-success">Live</span></div>
                    <div class="card-icon"><?= icon('building') ?></div>
                    <h3>Inventory that matches your project</h3>
                    <p>Project → tower → wing → floor → unit, with Available, On hold, Booked and Sold states, plus BHK, price, floor and area filters.</p>
                    <a class="feature-link" href="<?= e(site_url('web-panel')) ?>#inventory">See inventory <?= icon('arrow-right') ?></a>
                </article>
                <article class="card feature-card" id="calls">
                    <div class="feature-meta"><span class="pill pill-warning">App in development</span></div>
                    <div class="card-icon"><?= icon('phone') ?></div>
                    <h3>Every call keeps proof</h3>
                    <p>Calls start from your own SIM in the Rumu Android app. The recording syncs to your workspace with the outcome; if sync fails, upload it on web.</p>
                    <a class="feature-link" href="<?= e(site_url('calls')) ?>">How calls work <?= icon('arrow-right') ?></a>
                </article>
                <article class="card feature-card" id="free">
                    <div class="feature-meta"><span class="pill pill-success">Live</span></div>
                    <div class="card-icon card-icon--success"><?= icon('gift') ?></div>
                    <h3>Free first, then upgrade</h3>
                    <p>Basic costs nothing and covers 5 campaigns with 100 leads each. Move to Pro only when your pipeline justifies it.</p>
                    <a class="feature-link" href="<?= e(site_url('pricing')) ?>">Compare plans <?= icon('arrow-right') ?></a>
                </article>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="lead-detail-title">
        <div class="container split split--wide">
            <div>
                <p class="kicker">Lead detail</p>
                <h2 id="lead-detail-title">Kaun, kab, kya bola — sab ek jagah.</h2>
                <p class="lede">A profile rail with contact details and source, WhatsApp / Note / Activity tiles, a Calls tab, a clickable stage rail, activity timeline, notes, follow-ups and status audit trail.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Convert a won lead into a permanent customer.</span></li>
                    <li><?= icon('check') ?><span>Soft delete keeps customer and report views clean.</span></li>
                    <li><?= icon('check') ?><span>Status audit shows who changed the stage and when.</span></li>
                    <li><?= icon('check') ?><span>Calls tab carries outcome, playback and access history.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-primary" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="realtor-lead-detail">Start free</a>
                </div>
            </div>
            <div>
                <?= ui_mockup('lead-detail', ['caption' => 'Lead detail preview with sample data. Contact rail, activity, notes, follow-ups and audit trail.', 'aria_label' => 'Rumu lead detail screen with contact information, activity timeline and stage rail']) ?>
            </div>
        </div>
    </section>

    <section class="section section--soft" id="workflow" aria-labelledby="workflow-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Meta lead to site visit</p>
                <h2 id="workflow-title">The five-step workflow the product is built around.</h2>
            </div>
            <div class="steps">
                <div class="step">
                    <h3>Lead arrives</h3>
                    <p>Manual entry, CSV/XLSX, Meta CSV or real-time Meta Lead Gen sync.</p>
                </div>
                <div class="step">
                    <h3>Owner and stage</h3>
                    <p>Every lead gets a stage and an owner on the global lead board.</p>
                </div>
                <div class="step">
                    <h3>Follow-up planned</h3>
                    <p>A task or follow-up with a due date, linked to the lead.</p>
                </div>
                <div class="step">
                    <h3>Call or visit</h3>
                    <p>Call with outcome and recording, or schedule a site visit against an inventory unit.</p>
                </div>
                <div class="step">
                    <h3>Won → customer</h3>
                    <p>Convert the won lead into a customer; the history stays attached.</p>
                </div>
            </div>
            <div class="callout" style="margin-top:30px">
                <p><strong>Ad optimisation, honestly:</strong> CAPI sends conversion events back to Meta. We do not promise a percentage uplift — the product sends the signal, the ad platform decides what to do with it.</p>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="pricing-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Realtor pricing</p>
                <h2 id="pricing-title">Free se shuru, growth pe upgrade.</h2>
                <p>Live plan data from the product. Limits are shown exactly as they are enforced.</p>
            </div>
            <?php
            $plans = $realtorPlans;
            $currency = $pricing['currency'];
            $tax = $pricing['tax'];
            $compact = false;
            $audience = 'realtor';
            $pricingPrefix = 'realtor-plan';
            require __DIR__ . '/partials/pricing-cards.php';
            ?>
            <div class="button-row button-row--center" style="margin-top:26px">
                <a class="btn btn-ghost" href="<?= e(site_url('pricing')) ?>">Full comparison &amp; enterprise plans <?= icon('arrow-right', 'icon') ?></a>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="faq-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Realtor FAQ</p>
                <h2 id="faq-title">Before you start.</h2>
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
    $ctaTitle = 'Your leads are already coming in. Keep them.';
    $ctaText = 'Start with the free plan, import your first batch, and let the next follow-up show up on the board.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'See pricing';
    $ctaSecondaryUrl = site_url('pricing');
    $ctaId = 'realtor-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
