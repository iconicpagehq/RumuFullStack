<?php
require_once __DIR__ . '/lib/helpers.php';
http_response_code(404);

$page = [
    'title' => 'Page not found | Rumu',
    'description' => 'That page is not here. Head back to the Rumu home page, features or pricing.',
    'canonical_path' => '/404',
    'nav' => '',
    'page_id' => '404',
    'robots' => 'noindex, follow',
];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main id="main" class="error-page">
    <div class="container">
        <p class="error-code" aria-hidden="true">404</p>
        <h1>Yeh page nahi mila.</h1>
        <p>The link may be old or mistyped. The workspace, pricing and feature pages are all one click away.</p>
        <div class="button-row">
            <a class="btn btn-primary" href="<?= e(site_url('/')) ?>">Back to home</a>
            <a class="btn btn-secondary" href="<?= e(site_url('features')) ?>">See features</a>
            <a class="btn btn-ghost" href="<?= e(site_url('pricing')) ?>">See pricing</a>
        </div>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
