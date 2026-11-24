<?php
require_once __DIR__ . '/lib/helpers.php';

$page = [
    'title' => 'Rumu vs Excel, WhatsApp & Legacy CRM — An Honest Comparison',
    'description' => 'A factual comparison of Rumu against Excel, WhatsApp and legacy CRMs on setup, lead ownership, call history, team visibility, inventory and entry price.',
    'canonical_path' => '/why-rumu',
    'nav' => 'features',
    'page_id' => 'why-rumu',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Why Rumu' => '/why-rumu'])],
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Why Rumu</p>
            <h1 id="page-title">The honest comparison.</h1>
            <p class="lede">Excel is not the enemy. WhatsApp is not the enemy. The enemy is a follow-up that only exists in someone’s memory. Here is where a workspace actually changes the outcome.</p>
            <div class="button-row button-row--center">
                <a class="btn btn-primary btn-lg" href="<?= e(register_url('realtor')) ?>" data-event="cta_click" data-cta="why-start">Start free</a>
                <a class="btn btn-secondary btn-lg" href="<?= e(site_url('pricing')) ?>">See pricing</a>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="compare-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Capability, not mud-slinging</p>
                <h2 id="compare-title">What changes when the pipeline lives in one place.</h2>
                <p>Every cell is about capability. Competitor pricing and specific implementations vary, so we do not invent them.</p>
            </div>
            <p class="table-hint">Swipe sideways to compare →</p>
            <div class="table-wrap" role="region" aria-label="Capability comparison, scroll horizontally" tabindex="0">
                <table class="compare">
                    <thead>
                        <tr>
                            <th scope="col">What matters</th>
                            <th scope="col" class="is-rumu">Rumu</th>
                            <th scope="col">Excel</th>
                            <th scope="col">WhatsApp</th>
                            <th scope="col">Legacy CRM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Setup</td>
                            <td class="is-rumu">Signup, choose a plan, import a template</td>
                            <td>Instant, but you build the discipline</td>
                            <td>Instant, no pipeline structure</td>
                            <td>Usually an implementation project</td>
                        </tr>
                        <tr>
                            <td>Lead ownership and stage</td>
                            <td class="is-rumu">Every lead has an owner, stage and next follow-up</td>
                            <td>Manual columns; stale in a day</td>
                            <td>Chats, not shared ownership</td>
                            <td>Possible, but needs configuration</td>
                        </tr>
                        <tr>
                            <td>Call history</td>
                            <td class="is-rumu">Outcome, notes, private recording and access audit</td>
                            <td>Typed notes, no recording</td>
                            <td>Chat history only</td>
                            <td>Varies by module and provider</td>
                        </tr>
                        <tr>
                            <td>Team visibility</td>
                            <td class="is-rumu">Role-scoped hierarchy: admin, manager, employee</td>
                            <td>File sharing, no real permissions</td>
                            <td>Group chats, no accountability</td>
                            <td>Yes, with admin setup</td>
                        </tr>
                        <tr>
                            <td>Inventory</td>
                            <td class="is-rumu">Project → tower → wing → floor → unit availability</td>
                            <td>Possible with multiple sheets</td>
                            <td>No</td>
                            <td>Often an add-on or custom module</td>
                        </tr>
                        <tr>
                            <td>Follow-up reminders</td>
                            <td class="is-rumu">Tasks, due dates, overdue flags</td>
                            <td>Manual calendar discipline</td>
                            <td>Manual reminders</td>
                            <td>Yes, once configured</td>
                        </tr>
                        <tr>
                            <td>Data portability</td>
                            <td class="is-rumu">CSV export with formula-injection protection</td>
                            <td>You own the file</td>
                            <td>Limited export</td>
                            <td>Varies by vendor</td>
                        </tr>
                        <tr>
                            <td>Price of entry</td>
                            <td class="is-rumu">Free Basic plan</td>
                            <td>Free software, paid in hours</td>
                            <td>Free software, paid in leaks</td>
                            <td>Typical paid licence plus setup</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="tools-title">
        <div class="container">
            <div class="section-head">
                <p class="kicker">Be fair to the alternatives</p>
                <h2 id="tools-title">Each tool is good at something.</h2>
                <p>The question is whether the thing it is good at is the thing that is costing you deals.</p>
            </div>
            <div class="grid-3">
                <div class="card">
                    <div class="card-icon card-icon--ink"><?= icon('file') ?></div>
                    <h3>Excel</h3>
                    <p>Flexible, owned, familiar. Also: no reminders, no permissions, and one accidental sort can hide a lead. Great for analysis, weak for daily execution.</p>
                </div>
                <div class="card">
                    <div class="card-icon card-icon--ink"><?= icon('message') ?></div>
                    <h3>WhatsApp</h3>
                    <p>The fastest way to talk. Also: no stage, no team view, no call outcome, and the history walks out with the phone. Great for conversation, weak for pipeline.</p>
                </div>
                <div class="card">
                    <div class="card-icon card-icon--ink"><?= icon('sliders') ?></div>
                    <h3>Legacy CRM</h3>
                    <p>Depth, customisation and process. Also: long setup, training load and a price that assumes a large sales floor. Great for enterprise process, heavy for a small team.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="when-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">When not to buy</p>
                <h2 id="when-title">When Rumu is probably not worth it.</h2>
            </div>
            <div class="grid-2">
                <div class="card">
                    <h3>Stay with Excel if…</h3>
                    <ul class="mini-list">
                        <li><?= icon('check') ?><span>Your lead volume is small enough that memory works.</span></li>
                        <li><?= icon('check') ?><span>You already have a system your team follows every day.</span></li>
                        <li><?= icon('check') ?><span>You do not need calls, recordings or team scoping.</span></li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Use Rumu if…</h3>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>Leads arrive from more than one place and follow-ups slip.</span></li>
                        <li><?= icon('check') ?><span>You need to prove who called whom, and what the buyer said.</span></li>
                        <li><?= icon('check') ?><span>You manage a team and cannot see the work without asking for reports.</span></li>
                        <li><?= icon('check') ?><span>Inventory availability and site visits are part of the daily conversation.</span></li>
                    </ul>
                </div>
            </div>
            <div class="callout" style="margin-top:26px">
                <p><strong>No fake metrics:</strong> this page does not claim a percentage improvement. The product changes what is visible and attributable; your closing rate is still your team’s work.</p>
            </div>
        </div>
    </section>

    <?php
    $ctaTitle = 'Try it against your own pipeline.';
    $ctaText = 'Import a real lead list and see if the board, follow-ups and call history earn a place in your day.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'Start enterprise trial';
    $ctaSecondaryUrl = register_url('enterprise');
    $ctaId = 'why-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
