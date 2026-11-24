<?php
require_once __DIR__ . '/lib/helpers.php';

$page = [
    'title' => 'Contact Rumu — Support, Login & Start Free',
    'description' => 'Real contact paths only: start a workspace, log in, or email Rumu support. No demo form that disappears into a black hole.',
    'canonical_path' => '/contact',
    'nav' => 'contact',
    'page_id' => 'contact',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Contact' => '/contact'])],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Contact</p>
            <h1 id="page-title">Real paths. No black hole.</h1>
            <p class="lede">There is no “Book a demo” form here because there is no backend endpoint collecting it. Use a path that actually reaches a person or opens the product.</p>
        </div>
    </section>

    <section class="section" aria-labelledby="paths-title">
        <div class="container">
            <h2 id="paths-title" class="sr-only">Ways to reach Rumu</h2>
            <div class="contact-grid">
                <article class="contact-card">
                    <div class="card-icon"><?= icon('mail') ?></div>
                    <h2>Email support</h2>
                    <p>For account, billing or product questions, email the team. Include the email address on your workspace so we can find the account.</p>
                    <a class="btn btn-primary" href="<?= e(mailto_url('Rumu support')) ?>">Email <?= e(config('support_email')) ?></a>
                    <p class="small muted" style="margin-top:14px">This opens your mail app. It is a real mailbox, not a web form.</p>
                </article>
                <article class="contact-card">
                    <div class="card-icon card-icon--success"><?= icon('bolt') ?></div>
                    <h2>Start free</h2>
                    <p>No sales call required. Create a workspace, choose a plan inside the app and import your first leads.</p>
                    <a class="btn btn-primary" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="contact-start">Start free</a>
                    <p class="small muted" style="margin-top:14px">Basic is free · No credit card</p>
                </article>
                <article class="contact-card">
                    <div class="card-icon"><?= icon('building') ?></div>
                    <h2>Enterprise trial</h2>
                    <p>For builder and agency sales teams. Registration starts a 14-day trial; you can invite a manager and import real leads before committing.</p>
                    <a class="btn btn-secondary" href="<?= e(register_url('enterprise')) ?>" data-event="cta_click" data-cta="contact-enterprise">Start 14-day trial</a>
                </article>
                <article class="contact-card">
                    <div class="card-icon card-icon--ink"><?= icon('lock') ?></div>
                    <h2>Already have an account?</h2>
                    <p>Open the web workspace directly. If you need a security or recording-policy answer first, the trust centre has the detail.</p>
                    <div class="button-row">
                        <a class="btn btn-primary" href="<?= e(login_url()) ?>" data-event="login_click">Log in</a>
                        <a class="btn btn-secondary" href="<?= e(site_url('security')) ?>">Security</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="honesty-title">
        <div class="container legal-prose">
            <h2 id="honesty-title">Why there is no contact form</h2>
            <p>A form that stores your details without a real backend is worse than no form. Until a public contact endpoint exists with honeypot, timestamp checks, rate limiting and validation, the site links to real destinations only: mailto support, product registration and login.</p>
            <p>For legal notices, privacy requests or recording-policy questions, email <a href="<?= e(mailto_url('Rumu privacy enquiry')) ?>"><?= e(config('support_email')) ?></a> with a clear subject line.</p>
        </div>
    </section>

    <?php
    $ctaTitle = 'Skip the call. Open the workspace.';
    $ctaText = 'The fastest way to evaluate Rumu is to import a small batch of real leads and look at the board.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'See pricing';
    $ctaSecondaryUrl = site_url('pricing');
    $ctaId = 'contact-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
