<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/partials/ui-mockups.php';

$page = [
    'title' => 'App Calls & Recording Sync for Real Estate Sales Teams | Rumu',
    'description' => 'Call leads from the Rumu Android app on your own SIM and sync the recording to private workspace storage — with outcomes, playback and access audit.',
    'canonical_path' => '/calls',
    'nav' => 'features',
    'page_id' => 'calls',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Calls & recordings' => '/calls'])],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero" aria-labelledby="page-title">
        <div class="container">
            <div>
                <p class="kicker">Calls &amp; recordings</p>
                <h1 id="page-title">Calls that leave proof, not memory.</h1>
                <p class="lede">Call the lead from the Rumu Android app on your own SIM. The recording syncs back to the workspace with the outcome — so “what did the buyer say?” has an answer.</p>
                <div class="button-row">
                    <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="calls-hero-start">Start free</a>
                    <a class="btn btn-secondary btn-lg" href="<?= e(site_url('pricing')) ?>">See pricing</a>
                </div>
                <p class="hero-proof">
                    <span><?= icon('check') ?> Private storage</span>
                    <span><?= icon('check') ?> Access audit</span>
                    <span><?= icon('check') ?> Web upload fallback</span>
                </p>
            </div>
            <div class="page-hero-card">
                <h2>Where calls appear</h2>
                <ul class="mini-list">
                    <li><?= icon('check') ?><span>Calls page with stats and history</span></li>
                    <li><?= icon('check') ?><span>Dashboard calls card</span></li>
                    <li><?= icon('check') ?><span>Lead detail → Calls tab</span></li>
                </ul>
                <div class="callout" style="margin-top:16px">
                    <p>Calls start in the Android app (in development). The web workspace has no call button — it is where recordings and outcomes are reviewed.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="flow-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">The flow</p>
                <h2 id="flow-title">Four steps from dial to proof.</h2>
            </div>
            <div class="steps">
                <div class="step"><h3>Call from the app</h3><p>Tap Call on the lead in the Rumu Android app; your own SIM dialer opens.</p></div>
                <div class="step"><h3>The phone records</h3><p>The handset's call recorder saves the audio on the device.</p></div>
                <div class="step"><h3>The app syncs</h3><p>After the call, the app logs consent, duration and outcome, then uploads the recording.</p></div>
                <div class="step"><h3>Review on web and app</h3><p>The call appears with playback on the Calls page, dashboard card and the lead's Calls tab.</p></div>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="screen-title">
        <div class="container split split--wide">
            <div>
                <p class="kicker">In the workspace</p>
                <h2 id="screen-title">The call list is also the follow-up list.</h2>
                <p class="lede">Every row carries the person, the outcome and the duration. Playback opens an authenticated stream; the raw file path never reaches the browser.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>Outcomes: connected, not connected, busy, wrong number, call back, interested, not interested, converted, other.</span></li>
                    <li><?= icon('check') ?><span>Call notes and outcome stay on the lead timeline.</span></li>
                    <li><?= icon('check') ?><span>Each playback writes an access-audit row.</span></li>
                </ul>
                <div class="button-row">
                    <a class="btn btn-secondary" href="<?= e(site_url('web-panel')) ?>#calls">See the web panel <?= icon('arrow-right', 'icon') ?></a>
                </div>
            </div>
            <div>
                <?= ui_mockup('calls', ['caption' => 'Calls page preview with sample data: stats, outcomes and authenticated playback.', 'aria_label' => 'Rumu calls page with outcomes, durations and recording player']) ?>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="privacy-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Privacy model</p>
                <h2 id="privacy-title">Recording sirf authorised log sun sakte hain.</h2>
                <p>The recording is a private asset of your workspace, not a link on the open internet.</p>
            </div>
            <div class="security-diagram">
                <div class="layer">
                    <div class="layer-icon"><?= icon('lock') ?></div>
                    <div><h3>Private disk</h3><p>Recordings live under private storage, not a public web path.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('shield') ?></div>
                    <div><h3>Authenticated stream</h3><p>Playback requires a valid login and the right role; responses are marked private and no-store.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('clipboard') ?></div>
                    <div><h3>Access audit</h3><p>Every recording access records who played it and when.</p></div>
                </div>
                <div class="layer-arrow" aria-hidden="true">↓</div>
                <div class="layer">
                    <div class="layer-icon"><?= icon('eye') ?></div>
                    <div><h3>No file paths in the browser</h3><p>Rumu streams the file through its own authenticated endpoint; storage paths are never exposed to the client.</p></div>
                </div>
            </div>
            <div class="callout" style="margin-top:26px">
                <p><strong>Enterprise scoping:</strong> an employee plays their own calls, a manager the team subtree, an admin every call in the organisation. The API enforces the scope.</p>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="import-title">
        <div class="container split">
            <div>
                <p class="kicker">Fallback</p>
                <h2 id="import-title">App sync failed? Upload the recording on web.</h2>
                <p class="lede">If the phone could not send its file, the workspace keeps a manual upload path so the call history stays complete.</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span>MP3, WAV, M4A, MP4, OGG, AMR, AAC, 3GP and WebM.</span></li>
                    <li><?= icon('check') ?><span>Up to 50 MB per recording.</span></li>
                    <li><?= icon('check') ?><span>Attach it to the lead with the outcome and notes.</span></li>
                </ul>
            </div>
            <div class="card">
                <h3>Honest limits</h3>
                <ul class="mini-list">
                    <li><?= icon('x') ?><span>The Android app is still in development. Until it ships, calls can only be added through the web upload fallback.</span></li>
                    <li><?= icon('x') ?><span>Recording capture depends on the handset's built-in call recorder. Rumu does not record stealthily and never requests call-audio permissions.</span></li>
                    <li><?= icon('x') ?><span>There is no call button on the web dashboard — calls start only in the app.</span></li>
                    <li><?= icon('x') ?><span>No transcription or AI call summary — it is not built.</span></li>
                    <li><?= icon('x') ?><span>No automatic retention purge yet. Plan retention with your legal advisor.</span></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="legal-title">
        <div class="container legal-prose">
            <p class="kicker">Recording policy</p>
            <h2 id="legal-title">Consent and lawful use.</h2>
            <p>Recording laws and notice requirements differ by state and by circumstance. The customer is responsible for obtaining any consent required before recording a call. The app asks the user to confirm consent before a call is logged.</p>
            <p>Rumu keeps recordings private to authorised workspace users. Rumu does not provide legal advice on whether a particular call may be recorded.</p>
            <p><a href="<?= e(site_url('legal/recording')) ?>">Read the full recording policy</a> or <a href="<?= e(site_url('security')) ?>">see how access is controlled</a>.</p>
        </div>
    </section>

    <?php
    $ctaTitle = 'Keep the call. Keep the context.';
    $ctaText = 'Start free and attach outcomes and recordings to the lead from day one.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'Talk to support';
    $ctaSecondaryUrl = mailto_url('Rumu calls & recordings enquiry');
    $ctaNote = 'Calls start in the Android app · web upload fallback available';
    $ctaId = 'calls-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
