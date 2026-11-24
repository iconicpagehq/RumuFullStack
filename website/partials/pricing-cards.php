<?php
$plans = $plans ?? [];
$audience = $audience ?? 'realtor';
$currency = $currency ?? 'INR';
$tax = $tax ?? [];
$compact = $compact ?? false;
$pricingPrefix = $pricingPrefix ?? 'plan';

if ($plans === []) {
    ?>
    <div class="pricing-unavailable" role="status">
        <div class="pricing-unavailable-icon"><?= icon('sliders') ?></div>
        <div>
            <h3>Live pricing is temporarily unavailable</h3>
            <p>We show prices from the product database so you never see a stale number. Open the app to see the current plans, or start free and pick a plan inside the workspace.</p>
            <div class="button-row">
                <a class="btn btn-primary" href="<?= e(register_url($audience === 'enterprise' ? 'enterprise' : 'realtor')) ?>" data-event="cta_click" data-cta="pricing-unavailable"><?= $audience === 'enterprise' ? 'Start 14-day trial' : 'Start free' ?></a>
                <a class="btn btn-secondary" href="<?= e(login_url()) ?>" data-event="login_click">See current pricing</a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$maxPrice = 0.0;
foreach ($plans as $plan) {
    $maxPrice = max($maxPrice, (float) ($plan['price'] ?? 0));
}
$taxExtra = !empty($tax['enabled']) && empty($tax['inclusive']);
?>
<div class="pricing-grid pricing-grid--<?= e($audience) ?><?= $compact ? ' pricing-grid--compact' : '' ?>">
    <?php foreach ($plans as $plan):
        $price = (float) ($plan['price'] ?? 0);
        $featured = $maxPrice > 0 && $price === $maxPrice;
        $enterprise = is_enterprise_plan($plan);
        $planKey = preg_replace('/[^a-z0-9-]/', '-', strtolower((string) ($plan['key'] ?? 'plan')));
        $limits = [];
        if ($enterprise) {
            $limits[] = plan_limit($plan['max_managers'] ?? null) . ' managers';
            $limits[] = plan_limit($plan['max_employees'] ?? null) . ' employees';
            $limits[] = plan_limit($plan['max_teams'] ?? null) . ' teams';
            $limits[] = plan_limit($plan['max_monthly_leads'] ?? null) . ' leads / month';
            $limits[] = plan_limit($plan['max_inventory_units'] ?? null) . ' inventory units';
        } else {
            $limits[] = ($plan['max_campaigns'] ?? null) === null ? 'Unlimited campaigns' : plan_limit($plan['max_campaigns']) . ' campaigns';
            $limits[] = ($plan['max_leads_per_campaign'] ?? null) === null ? 'Unlimited leads per campaign' : plan_limit($plan['max_leads_per_campaign']) . ' leads per campaign';
        }
        if (($plan['max_campaigns'] ?? null) !== null && $enterprise) {
            $limits[] = plan_limit($plan['max_campaigns']) . ' campaigns';
        }
        ?>
        <article class="plan-card<?= $featured ? ' plan-card--featured' : '' ?>" id="<?= e($pricingPrefix . '-' . $planKey) ?>">
            <div class="plan-card-head">
                <div class="plan-title-row">
                    <h3><?= e($plan['name'] ?? 'Plan') ?></h3>
                    <?php if ($price <= 0): ?>
                        <span class="pill pill-success">Free</span>
                    <?php elseif ($featured): ?>
                        <span class="pill pill-brand"><?= $enterprise ? 'For serious teams' : 'Full workspace' ?></span>
                    <?php endif; ?>
                </div>
                <p class="plan-price">
                    <?= e(plan_price($plan, $currency)) ?>
                    <?php if ($price > 0): ?><span class="plan-period">/ month</span><?php endif; ?>
                </p>
                <p class="plan-sub">
                    <?php if ($price <= 0 && $enterprise): ?>
                        Free trial setup · 14 days to explore
                    <?php elseif ($price <= 0): ?>
                        No credit card required
                    <?php elseif ($enterprise): ?>
                        Starts with a 14-day trial<?= $taxExtra ? ' · plus GST' : '' ?>
                    <?php else: ?>
                        Billed in the app<?= $taxExtra ? ' · plus GST' : '' ?>
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!$compact): ?>
                <ul class="plan-limits">
                    <?php foreach ($limits as $limit): ?>
                        <li><?= icon('check') ?><span><?= e($limit) ?></span></li>
                    <?php endforeach; ?>
                    <?php if (!empty($plan['advanced_reports'])): ?>
                        <li><?= icon('check') ?><span>Advanced reports &amp; exports</span></li>
                    <?php endif; ?>
                    <?php if (!empty($plan['meta_integrations'])): ?>
                        <li><?= icon('check') ?><span>Enterprise Meta integrations</span></li>
                    <?php endif; ?>
                </ul>
            <?php else: ?>
                <ul class="plan-limits plan-limits--compact">
                    <?php foreach (array_slice($limits, 0, 3) as $limit): ?>
                        <li><?= icon('check') ?><span><?= e($limit) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a class="btn <?= $featured ? 'btn-primary' : 'btn-secondary' ?> btn-block" href="<?= e(plan_cta_url($plan)) ?>" data-event="plan_cta" data-plan="<?= e((string) ($plan['key'] ?? '')) ?>">
                <?= e(plan_cta_label($plan)) ?>
            </a>
            <?php if ($enterprise && !$compact): ?>
                <p class="plan-foot">Seats and plan changes are handled from workspace billing.</p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php if ($taxExtra && !$compact): ?>
    <p class="pricing-tax-note">Prices shown in <?= e($currency) ?>. GST is added where applicable.</p>
<?php endif; ?>
