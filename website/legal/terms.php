<?php
require_once dirname(__DIR__) . '/lib/helpers.php';

$page = [
    'title' => 'Terms of Service | Rumu',
    'description' => 'Terms for using the Rumu web workspace: accounts, plans, trials, acceptable use, data ownership, recordings and liability.',
    'canonical_path' => '/legal/terms',
    'nav' => '',
    'page_id' => 'legal-terms',
    'json_ld' => [breadcrumb_schema(['Home' => '/', 'Terms of service' => '/legal/terms'])],
];
require dirname(__DIR__) . '/partials/head.php';
require dirname(__DIR__) . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Legal</p>
            <h1 id="page-title">Terms of service</h1>
            <p class="lede">The rules for using the Rumu workspace.</p>
        </div>
    </section>

    <section class="section">
        <div class="container legal-prose">
            <p class="legal-meta">Last updated: <?= e(config('launch_date')) ?> · By creating an account or using the workspace you agree to these terms.</p>

            <h2>1. The service</h2>
            <p>Rumu provides a web-based real-estate sales workspace for lead management, follow-ups, tasks, site visits, inventory, calls and reporting. Enterprise workspaces add hierarchy, team distribution, targets and governance features. Features described as roadmap or “coming soon” are not part of the current service.</p>

            <h2>2. Accounts</h2>
            <ul>
                <li>You must provide accurate account information and keep your credentials secure.</li>
                <li>You are responsible for activity under your account and for the actions of members you invite.</li>
                <li>Enterprise administrators control membership, roles and data scope for their organisation.</li>
                <li>Notify support promptly if you suspect unauthorised access.</li>
            </ul>

            <h2>3. Plans, trials and billing</h2>
            <ul>
                <li>Plan names, prices and limits are shown from the product database and may change. The current price at the time of a plan change applies.</li>
                <li>Free plans and paid plans have enforced limits such as campaign, lead, manager, employee, team and inventory-unit caps.</li>
                <li>Enterprise accounts start with a 14-day trial. Trial expiry is not yet hard-enforced; plan changes and seat additions go through workspace billing.</li>
                <li>Annual billing discounts are not offered today.</li>
                <li>GST and other applicable taxes are calculated during billing. Invoices are available as PDF downloads.</li>
                <li>Fees are not refundable except where required by law or expressly agreed in writing. Downgrades and cancellations limit access according to the applicable plan.</li>
            </ul>

            <h2>4. Acceptable use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Record calls without any consent or notice required by applicable law.</li>
                <li>Use the service for spam, unlawful marketing or harassment.</li>
                <li>Attempt to access another workspace, tenant or user’s data without authorisation.</li>
                <li>Upload malware or interfere with the service’s security or availability.</li>
                <li>Resell or sublicense the service without written permission.</li>
                <li>Use the service to violate third-party rights, including privacy and intellectual-property rights.</li>
            </ul>

            <h2>5. Your data</h2>
            <p>You own the lead, customer and workspace content you submit. You grant Rumu the limited right to process that content to provide and secure the service. You are responsible for having a lawful basis to store contact data, to contact leads, and to record calls.</p>
            <p>Leads can be exported as CSV. Recording retention has no automatic purge today; you are responsible for setting an internal retention policy. See the <a href="<?= e(site_url('legal/privacy')) ?>">privacy policy</a> and <a href="<?= e(site_url('legal/recording')) ?>">recording policy</a>.</p>

            <h2>6. Third-party services</h2>
            <p>The workspace may connect to services such as Meta, an email provider or a payment gateway when you enable them. Those services have their own terms and availability. Rumu is not responsible for a third party’s outages, decisions or policies, including an ad platform’s use of conversion signals.</p>

            <h2>7. Availability and changes</h2>
            <p>We work to keep the service available and may perform maintenance. Features can change, be improved or be withdrawn. Roadmap items are plans, not commitments or delivery guarantees. We may suspend accounts that threaten security, abuse the service or breach these terms.</p>

            <h2>8. Intellectual property</h2>
            <p>The Rumu name, interface, software and documentation are owned by Rumu or its licensors. These terms do not grant you a right to copy, reverse engineer or redistribute the service except as allowed by law.</p>

            <h2>9. Disclaimers</h2>
            <p>The service is provided on an “as available” basis. Rumu does not guarantee a particular sales outcome, lead quality, ad-performance uplift or legal compliance for your recording practices. Rumu does not provide legal advice. You are responsible for consent notices and for verifying that calls may be recorded.</p>

            <h2>10. Limitation of liability</h2>
            <p>To the maximum extent permitted by law, Rumu is not liable for indirect, incidental, special or consequential loss, or for lost profits, lost leads or lost data arising from your use of the service. Nothing in these terms limits liability that cannot be limited by law.</p>

            <h2>11. Termination</h2>
            <p>You may stop using the service at any time. We may suspend or terminate access for breach, non-payment, security risk or unlawful use. Where reasonable and lawful, we will allow export of your leads before termination.</p>

            <h2>12. Governing law</h2>
            <p>These terms are governed by the laws of India. Disputes are subject to the courts that have jurisdiction under applicable Indian law.</p>

            <h2>13. Contact</h2>
            <p>Questions about these terms: <a href="<?= e(mailto_url('Rumu terms enquiry')) ?>"><?= e(config('support_email')) ?></a>.</p>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
