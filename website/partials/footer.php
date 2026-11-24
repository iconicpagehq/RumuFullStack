<?php
$footerYear = date('Y');
?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand--footer" href="<?= e(site_url('/')) ?>" aria-label="Rumu home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <rect width="64" height="64" rx="14" fill="#E9B8DF"/>
                        <path d="M32 9 8 27.5V54h18V39a6 6 0 0 1 12 0v15h18V27.5L32 9Z" fill="#650052"/>
                        <path d="M26 54V39a6 6 0 0 1 12 0v15Z" fill="#E9B8DF"/>
                    </svg>
                </span>
                <span class="brand-word">Rumu</span>
            </a>
            <p>The real-estate sales workspace that turns enquiries into site visits.</p>
            <p class="footer-status"><span class="status-dot" aria-hidden="true"></span> Web panel available now<br><span class="footer-status-muted">Android app in development</span></p>
        </div>

        <nav class="footer-col" aria-label="Product">
            <h2>Product</h2>
            <a href="<?= e(site_url('web-panel')) ?>">Web panel</a>
            <a href="<?= e(site_url('calls')) ?>">Calls &amp; recordings</a>
            <a href="<?= e(site_url('features')) ?>">All features</a>
            <a href="<?= e(site_url('app')) ?>">Android app <span class="pill pill-mini">Coming soon</span></a>
            <a href="<?= e(site_url('why-rumu')) ?>">Why Rumu</a>
        </nav>

        <nav class="footer-col" aria-label="Editions">
            <h2>Editions</h2>
            <a href="<?= e(site_url('for-realtors')) ?>">For Realtors</a>
            <a href="<?= e(site_url('for-enterprises')) ?>">For Enterprises</a>
            <a href="<?= e(site_url('pricing')) ?>">Pricing</a>
            <a href="<?= e(register_url('realtor')) ?>">Start free</a>
            <a href="<?= e(register_url('enterprise')) ?>">Start enterprise trial</a>
        </nav>

        <nav class="footer-col" aria-label="Trust">
            <h2>Trust</h2>
            <a href="<?= e(site_url('security')) ?>">Security &amp; access</a>
            <a href="<?= e(site_url('legal/recording')) ?>">Recording policy</a>
            <a href="<?= e(site_url('contact')) ?>">Contact</a>
        </nav>

        <nav class="footer-col" aria-label="Legal">
            <h2>Legal</h2>
            <a href="<?= e(site_url('legal/privacy')) ?>">Privacy policy</a>
            <a href="<?= e(site_url('legal/terms')) ?>">Terms of service</a>
            <a href="<?= e(site_url('legal/recording')) ?>">Recording consent</a>
            <a href="<?= e(mailto_url('Rumu support')) ?>"><?= e(config('support_email')) ?></a>
        </nav>
    </div>

    <div class="container footer-bottom">
        <p>© <?= e($footerYear) ?> Rumu. Built for Indian real-estate teams.</p>
        <p class="footer-note">No fake logos. No invented metrics. Every claim maps to a shipped feature.</p>
    </div>
</footer>
<script src="<?= e(asset_url('js/site.js')) ?>" defer></script>
</body>
</html>
