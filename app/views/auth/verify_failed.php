<section class="auth-section narrow">
    <div class="auth-card single">
        <div class="auth-form center">
            <span class="auth-icon auth-icon-danger"><?= icon('x-circle') ?></span>
            <h1><?= e(t('Invalid or expired verification link')) ?></h1>
            <p class="muted"><?= e(t('The link may have expired (links are valid for 24 hours) or was already used. Request a new one below.')) ?></p>
            <form method="post" action="<?= e(url('auth/resend-verification')) ?>" data-ajax novalidate>
                <?= csrf_field() ?>
                <div class="field"><div class="input-icon"><?= icon('mail') ?><input type="email" name="email" placeholder="<?= e(t('Enter your email address')) ?>" required></div><?= field_error('email') ?></div>
                <button class="btn btn-primary btn-block" type="submit"><?= e(t('Resend verification email')) ?></button>
            </form>
            <p class="auth-switch"><a href="<?= e(url('auth/login')) ?>"><?= icon('arrow-left', 'icon-xs') ?> <?= e(t('Back to Login')) ?></a></p>
        </div>
    </div>
</section>
