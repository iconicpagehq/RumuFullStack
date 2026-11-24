<?php
require_once dirname(__DIR__) . '/lib/helpers.php';

$page = [
    'title' => 'Call Recording Policy & Consent | Rumu',
    'description' => 'How Rumu handles call recordings: the Android app syncs the handset recording, consent responsibility, private storage, access audit and retention limits.',
    'canonical_path' => '/legal/recording',
    'nav' => '',
    'page_id' => 'legal-recording',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Recording policy' => '/legal/recording'])],
];
require dirname(__DIR__) . '/partials/head.php';
require dirname(__DIR__) . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Legal</p>
            <h1 id="page-title">Call recording policy</h1>
            <p class="lede">Recording ho, toh baat yaad rehti hai. Here is exactly how it works, and what stays your responsibility.</p>
        </div>
    </section>

    <section class="section">
        <div class="container legal-prose">
            <p class="legal-meta">Last updated: <?= e(config('launch_date')) ?> · Read with the <a href="<?= e(site_url('legal/privacy')) ?>">privacy policy</a> and <a href="<?= e(site_url('security')) ?>">security page</a>.</p>

            <div class="honest-note">
                <?= icon('phone') ?>
                <p><strong>Not legal advice.</strong> Recording laws vary by state and circumstances. You are responsible for obtaining any consent or giving any notice required before recording a call.</p>
            </div>

            <h2>1. Who records</h2>
            <p>Rumu does not record audio itself and does not capture call audio in the background. Calls are placed from the Rumu Android app on the user's own SIM. If the handset's built-in call recorder produces a file, the app uploads that file — after the user confirms consent — so Rumu can attach it to the call record.</p>
            <p>The Android app is in development. Until it ships, the web workspace offers a manual recording upload so call history is not lost; the dashboard has no call button and no cloud telephony bridge.</p>

            <h2>2. Consent and notice</h2>
            <ul>
                <li>The customer decides whether recording is lawful for a given call and is responsible for any consent, disclosure or notice.</li>
                <li>The app asks the user to confirm consent before a call is logged; the wording and legal adequacy of any notice remain your responsibility.</li>
                <li>Do not use Rumu to record a call when the law or the other party’s consent requirements forbid it.</li>
            </ul>

            <h2>3. Storage and access</h2>
            <ul>
                <li>Recordings are stored on a private disk, not a public web path.</li>
                <li>Playback requires authentication and a role that is allowed to access the call.</li>
                <li>Responses are marked <code>Cache-Control: private, no-store</code>.</li>
                <li>Raw storage paths are never exposed to the browser; Rumu streams the file itself.</li>
                <li>Each recording access writes an audit row with the actor and time.</li>
                <li>Enterprise scoping applies: an employee sees their own calls, a manager the team subtree, an admin the organisation.</li>
            </ul>

            <h2>4. Web upload fallback</h2>
            <p>If the app cannot sync a file, you can upload the recording from the web workspace. Supported formats include MP3, WAV, M4A, MP4, OGG, AMR, AAC, 3GP and WebM, up to 50 MB. Uploaded recordings follow the same private-storage and access-log rules.</p>

            <h2>5. No transcription or AI summary</h2>
            <p>Rumu does not generate transcripts or AI call summaries today. Do not assume an automated summary exists. The recording and the outcome/notes are the record.</p>

            <h2>6. Retention</h2>
            <p>There is no automatic retention purge yet. Recordings remain until the customer removes them or account-level deletion is processed. You should define an internal retention policy appropriate to your business and legal obligations.</p>

            <h2>7. Legal requests and disputes</h2>
            <p>If you receive a legal request for a recording, take independent legal advice. Rumu may disclose information where required by law, as described in the privacy policy.</p>

            <h2>8. Contact</h2>
            <p>Recording-policy questions: <a href="<?= e(mailto_url('Rumu recording policy enquiry')) ?>"><?= e(config('support_email')) ?></a>.</p>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
