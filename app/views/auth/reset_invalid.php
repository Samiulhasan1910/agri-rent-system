<section class="auth-section narrow">
    <div class="auth-card single">
        <div class="auth-form center">
            <span class="auth-icon auth-icon-danger"><?= icon('x-circle') ?></span>
            <h1><?= e(t('Invalid or expired reset link')) ?></h1>
            <p class="muted"><?= e(t('This password reset link is no longer valid. Please request a new one.')) ?></p>
            <a class="btn btn-primary btn-block" href="<?= e(url('auth/forgot')) ?>"><?= e(t('Request a new link')) ?></a>
            <p class="auth-switch"><a href="<?= e(url('auth/login')) ?>"><?= icon('arrow-left', 'icon-xs') ?> <?= e(t('Back to Login')) ?></a></p>
        </div>
    </div>
</section>
