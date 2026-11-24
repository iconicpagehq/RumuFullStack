<?php
require_once __DIR__ . '/lib/helpers.php';

$pricing = fetch_plans();
$realtorPlans = $pricing['plans']['realtor'];
$enterprisePlans = $pricing['plans']['enterprise'];
$currency = $pricing['currency'];
$tax = $pricing['tax'];
$taxExtra = !empty($tax['enabled']) && empty($tax['inclusive']);
$defaultEdition = (($_GET['edition'] ?? '') === 'enterprise') ? 'enterprise' : 'realtor';

$faqs = [
    ['q' => 'How do I upgrade or change a plan?', 'a' => 'Realtor plans can be changed from checkout and the workspace billing screens; upgrades may be requested inside the app. Enterprise plan and seat changes go through workspace billing. The limits in the comparison table are the limits the API enforces.'],
    ['q' => 'Do you offer annual billing discounts?', 'a' => 'No annual discount exists in the product today, so we do not advertise one. The price you see is the price in the product database.'],
    ['q' => 'Are invoices available?', 'a' => 'Yes. Billing includes invoices as PDF downloads and usage counters. Enterprise procurement can also request seat add-ons, and those actions are recorded.'],
    ['q' => 'What happens if I downgrade or cancel?', 'a' => 'Your data stays in the workspace and leads remain exportable as CSV. Access limits apply based on the plan. Billing changes and any refund handling follow the records in your workspace; there is no blanket refund promise on this page.'],
    ['q' => 'Is GST included in the price?', 'a' => $taxExtra ? 'No. Prices exclude GST where the product has tax enabled at an exclusive rate. The applicable GST is calculated during billing.' : 'Tax treatment is calculated during billing. The prices shown come from the product database.'],
    ['q' => 'What does the enterprise trial include?', 'a' => 'Enterprise registration starts a 14-day trial. We do not promise an automatic lockout at day 15, because trial expiry is not yet hard-enforced; plan changes are managed from workspace billing.'],
];

$offers = [];
if ($pricing['ok']) {
    foreach (array_merge($realtorPlans, $enterprisePlans) as $plan) {
        $offers[] = [
            '@type' => 'Offer',
            'name' => $plan['name'],
            'price' => (float) $plan['price'],
            'priceCurrency' => $currency,
            'url' => plan_register_url($plan),
        ];
    }
}

$page = [
    'title' => 'Rumu Pricing — Free Plan, Pro & Enterprise Plans | Rumu',
    'description' => 'Start free. Compare realtor and enterprise plans with exact limits, live prices and GST notes. Prices are fetched from the product database, not hardcoded.',
    'canonical_path' => '/pricing',
    'nav' => 'pricing',
    'page_id' => 'pricing',
    'json_ld' => [
        breadcrumb_schema(['Home' => '/', 'Pricing' => '/pricing']),
        faq_schema($faqs),
        [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Rumu',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'offers' => $offers,
        ],
    ],
];

function pricing_table(array $plans, string $audience, string $currency, array $tax): void
{
    if ($plans === []) {
        return;
    }
    $maxPrice = 0.0;
    foreach ($plans as $plan) {
        $maxPrice = max($maxPrice, (float) ($plan['price'] ?? 0));
    }
    $rows = [
        ['Price', static fn ($p) => plan_price($p, $currency) . ((float) $p['price'] > 0 ? ' / month' : '')],
        ['Campaigns', static fn ($p) => plan_limit($p['max_campaigns'] ?? null)],
        ['Leads per campaign', static fn ($p) => plan_limit($p['max_leads_per_campaign'] ?? null)],
    ];
    if ($audience === 'enterprise') {
        array_push(
            $rows,
            ['Managers', static fn ($p) => plan_limit($p['max_managers'] ?? null)],
            ['Employees', static fn ($p) => plan_limit($p['max_employees'] ?? null)],
            ['Teams', static fn ($p) => plan_limit($p['max_teams'] ?? null)],
            ['Monthly leads', static fn ($p) => plan_limit($p['max_monthly_leads'] ?? null)],
            ['Inventory units', static fn ($p) => plan_limit($p['max_inventory_units'] ?? null)],
            ['Advanced reports', static fn ($p) => !empty($p['advanced_reports']) ? '<span class="yes">Included</span>' : '<span class="no">Not included</span>'],
            ['Enterprise Meta integrations', static fn ($p) => !empty($p['meta_integrations']) ? '<span class="yes">Included</span>' : '<span class="no">Not included</span>'],
        );
    }
    array_push(
        $rows,
        ['Lead board &amp; table view', static fn ($p) => '<span class="yes">Included</span>'],
        ['Calls &amp; app-synced recordings', static fn ($p) => '<span class="yes">Included</span>'],
        ['Formula-safe CSV export', static fn ($p) => '<span class="yes">Included</span>'],
    );
    ?>
    <p class="table-hint">Swipe sideways to compare →</p>
    <div class="table-wrap" role="region" aria-label="Plan comparison, scroll horizontally" tabindex="0">
        <table class="compare">
            <thead>
                <tr>
                    <th scope="col">Limit</th>
                    <?php foreach ($plans as $plan): ?>
                        <th scope="col" class="<?= ((float) $plan['price'] === $maxPrice && $maxPrice > 0) ? 'is-rumu' : '' ?>"><?= e($plan['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as [$label, $resolver]): ?>
                    <tr>
                        <td><?= $label ?></td>
                        <?php foreach ($plans as $plan): ?>
                            <td><?= $resolver($plan) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($audience === 'enterprise'): ?>
        <p class="pricing-tax-note">Enterprise accounts start with a 14-day trial. Prices in <?= e($currency) ?><?= (!empty($tax['enabled']) && empty($tax['inclusive'])) ? ' · GST added where applicable' : '' ?>.</p>
    <?php endif;
}

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main">
    <section class="page-hero page-hero--center" aria-labelledby="page-title">
        <div class="container">
            <p class="kicker">Pricing</p>
            <h1 id="page-title">Start free. Upgrade when the pipeline pays for it.</h1>
            <p class="lede">Free se shuru, growth pe upgrade. Prices and limits come straight from the product database, so what you see here matches what the app will charge.</p>
            <p class="hero-proof" style="justify-content:center">
                <span><?= icon('check') ?> Free Basic plan</span>
                <span><?= icon('check') ?> Enterprise 14-day trial</span>
                <span><?= icon('check') ?> GST shown at billing</span>
            </p>
        </div>
    </section>

    <section class="section" aria-labelledby="plans-title">
        <div class="container">
            <div class="pricing-switch">
                <div class="tabs" role="tablist" aria-label="Plan audience" data-tabs>
                    <button type="button" class="tab" role="tab" id="tab-price-realtor" aria-selected="<?= $defaultEdition === 'realtor' ? 'true' : 'false' ?>" aria-controls="panel-price-realtor" data-tab-target="realtor">For Realtors</button>
                    <button type="button" class="tab" role="tab" id="tab-price-enterprise" aria-selected="<?= $defaultEdition === 'enterprise' ? 'true' : 'false' ?>" aria-controls="panel-price-enterprise" data-tab-target="enterprise">For Enterprises</button>
                </div>
                <p class="small muted">Free plan first. Limits shown exactly as enforced.</p>
            </div>

            <div class="tab-panel" role="tabpanel" id="panel-price-realtor" aria-labelledby="tab-price-realtor" data-tab-panel="realtor" <?= $defaultEdition !== 'realtor' ? 'hidden' : '' ?>>
                <h2 id="plans-title" class="sr-only">Realtor plans</h2>
                <?php
                $plans = $realtorPlans;
                $audience = 'realtor';
                $compact = false;
                $pricingPrefix = 'price-realtor';
                require __DIR__ . '/partials/pricing-cards.php';
                ?>
                <div style="margin-top:34px">
                    <?php pricing_table($realtorPlans, 'realtor', $currency, $tax); ?>
                </div>
            </div>

            <div class="tab-panel" role="tabpanel" id="panel-price-enterprise" aria-labelledby="tab-price-enterprise" data-tab-panel="enterprise" <?= $defaultEdition !== 'enterprise' ? 'hidden' : '' ?>>
                <h2 class="sr-only">Enterprise plans</h2>
                <?php
                $plans = $enterprisePlans;
                $audience = 'enterprise';
                $compact = false;
                $pricingPrefix = 'price-enterprise';
                require __DIR__ . '/partials/pricing-cards.php';
                ?>
                <div style="margin-top:34px">
                    <?php pricing_table($enterprisePlans, 'enterprise', $currency, $tax); ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="included-title">
        <div class="container">
            <div class="included-band">
                <h3 id="included-title">What is included in every plan</h3>
                <div class="grid-3">
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>One workspace with every lead source</span></li>
                        <li><?= icon('check') ?><span>Global lead board with stages and owners</span></li>
                    </ul>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>Follow-ups, tasks and site visits</span></li>
                        <li><?= icon('check') ?><span>Calls module and app-synced recordings</span></li>
                    </ul>
                    <ul class="check-list">
                        <li><?= icon('check') ?><span>Inventory hierarchy with availability</span></li>
                        <li><?= icon('check') ?><span>Formula-safe CSV export</span></li>
                    </ul>
                </div>
            </div>
            <div class="callout" style="margin-top:24px">
                <p><strong>What we do not do:</strong> no annual billing discounts, no fake scarcity, no automatic lockout at trial day 15, and no refund promise the product does not implement. Billing changes are visible in the workspace.</p>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="faq-title">
        <div class="container">
            <div class="section-head section-head--center">
                <p class="kicker">Pricing FAQ</p>
                <h2 id="faq-title">Upgrades, invoices and cancellation.</h2>
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
    $ctaTitle = 'Pick the plan that matches your pipeline today.';
    $ctaText = 'Start on Basic for free, or evaluate the enterprise hierarchy with a 14-day trial.';
    $ctaPrimaryLabel = 'Start free';
    $ctaPrimaryUrl = register_url('realtor');
    $ctaSecondaryLabel = 'Start enterprise trial';
    $ctaSecondaryUrl = register_url('enterprise');
    $ctaId = 'pricing-cta';
    require __DIR__ . '/partials/cta.php';
    ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
