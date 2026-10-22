<!doctype html>
<html lang="<?= Lang::get() ?>" data-lang="<?= Lang::get() ?>">
<head><?php include __DIR__ . '/_head.php'; ?></head>
<body class="public">
<a class="skip-link" href="#main"><?= e(t('Skip to content')) ?></a>
<header class="site-nav">
    <div class="container site-nav-inner">
        <a class="brand" href="<?= e(url('home/index')) ?>"><?= icon('leaf') ?><span><?= e(setting('platform_name', 'AgriRent')) ?></span></a>
        <button class="btn-icon nav-toggle" type="button" data-toggle-class="nav-open" data-target="body" aria-label="<?= e(t('Menu')) ?>"><?= icon('menu') ?></button>
        <nav class="site-links" aria-label="<?= e(t('Main')) ?>">
            <a href="<?= e(url('home/index')) ?>#top" class="<?= Router::current() === 'home/index' ? 'active' : '' ?>"><?= e(t('Home')) ?></a>
            <a href="<?= e(url('home/index')) ?>#about"><?= e(t('About')) ?></a>
            <a href="<?= e(url('home/index')) ?>#how"><?= e(t('How It Works')) ?></a>
            <a href="<?= e(url('home/index')) ?>#contact"><?= e(t('Contact')) ?></a>
        </nav>
        <div class="site-actions">
            <?php include __DIR__ . '/_lang.php'; ?>
            <?php if (Auth::check()): ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('dashboard/index')) ?>" aria-label="<?= e(t('Dashboard')) ?>"><?= icon('home') ?><span><?= e(t('Dashboard')) ?></span></a>
            <?php else: ?>
                <a class="btn btn-outline btn-sm" href="<?= e(url('auth/register')) ?>" aria-label="<?= e(t('Register')) ?>"><?= icon('user') ?><span><?= e(t('Register')) ?></span></a>
                <a class="btn btn-primary btn-sm" href="<?= e(url('auth/login')) ?>" aria-label="<?= e(t('Login')) ?>"><?= icon('login') ?><span><?= e(t('Login')) ?></span></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="main" class="public-main">
    <?= $content ?>
</main>

<footer class="site-footer" id="contact">
    <div class="container footer-grid">
        <div>
            <a class="brand brand-light" href="<?= e(url('home/index')) ?>"><?= icon('leaf') ?><span><?= e(setting('platform_name', 'AgriRent')) ?></span></a>
            <p><?= e(t('Rent quality agricultural equipment easily and securely.')) ?></p>
        </div>
        <div>
            <h4><?= e(t('Quick Links')) ?></h4>
            <a href="<?= e(url('home/index')) ?>#about"><?= e(t('About Us')) ?></a>
            <a href="<?= e(url('home/index')) ?>#how"><?= e(t('How It Works')) ?></a>
            <a href="<?= e(url('auth/register')) ?>"><?= e(t('Create Account')) ?></a>
        </div>
        <div>
            <h4><?= e(t('Support')) ?></h4>
            <a href="mailto:<?= e(setting('support_email')) ?>"><?= icon('mail', 'icon-xs') ?> <?= e(setting('support_email')) ?></a>
            <a href="tel:<?= e(setting('support_phone')) ?>"><?= icon('phone', 'icon-xs') ?> <?= e(t('Hotline')) ?> <?= e(setting('support_phone')) ?></a>
            <span><?= icon('clock', 'icon-xs') ?> <?= e(t('9 AM - 9 PM (Daily)')) ?></span>
        </div>
        <div>
            <h4><?= e(t('Connect')) ?></h4>
            <div class="social">
                <a href="https://facebook.com" aria-label="Facebook" rel="noopener" target="_blank">f</a>
                <a href="https://instagram.com" aria-label="Instagram" rel="noopener" target="_blank">◎</a>
                <a href="https://youtube.com" aria-label="YouTube" rel="noopener" target="_blank">▶</a>
            </div>
        </div>
    </div>
    <div class="container footer-bottom">© <?= date('Y') ?> <?= e(setting('platform_name', 'AgriRent')) ?> · <?= e(t('Agricultural Equipment Rental and Management System')) ?></div>
</footer>
<?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/modal.php'; ?>
</body>
</html>
