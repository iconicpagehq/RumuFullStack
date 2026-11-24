<?php
$ctaTitle = $ctaTitle ?? 'Start free. Upgrade when the pipeline pays for it.';
$ctaText = $ctaText ?? 'Open the workspace, import your first leads and see every follow-up in one place.';
$ctaPrimaryLabel = $ctaPrimaryLabel ?? 'Start free';
$ctaPrimaryUrl = $ctaPrimaryUrl ?? register_url('realtor');
$ctaSecondaryLabel = $ctaSecondaryLabel ?? 'See pricing';
$ctaSecondaryUrl = $ctaSecondaryUrl ?? site_url('pricing');
$ctaNote = $ctaNote ?? 'Free plan · No credit card · Your data stays yours';
$ctaId = $ctaId ?? 'footer-cta';
?>
<section class="cta-band" aria-labelledby="<?= e($ctaId) ?>-title">
    <div class="container cta-band-inner">
        <div class="cta-copy">
            <p class="kicker kicker--light">Ready when you are</p>
            <h2 id="<?= e($ctaId) ?>-title"><?= e($ctaTitle) ?></h2>
            <p><?= e($ctaText) ?></p>
        </div>
        <div class="cta-actions">
            <a class="btn btn-light btn-lg" href="<?= e($ctaPrimaryUrl) ?>" data-event="cta_click" data-cta="<?= e($ctaId) ?>-primary"><?= e($ctaPrimaryLabel) ?></a>
            <a class="btn btn-outline-light btn-lg" href="<?= e($ctaSecondaryUrl) ?>" data-event="cta_click" data-cta="<?= e($ctaId) ?>-secondary"><?= e($ctaSecondaryLabel) ?></a>
            <p class="cta-note"><?= e($ctaNote) ?></p>
        </div>
    </div>
</section>
