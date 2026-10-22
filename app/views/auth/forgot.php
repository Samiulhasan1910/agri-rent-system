<section class="auth-section">
    <div class="auth-card">
        <aside class="auth-aside">
            <h2><?= e(t('No worries,')) ?><br><span><?= e(t("we've got you!")) ?></span></h2>
            <p><?= e(t("Enter your registered email address and we'll send you a link and code to reset your password.")) ?></p>
            <ul class="auth-features">
                <li><span><?= icon('shield-check') ?></span><div><strong><?= e(t('Secure & Safe')) ?></strong><small><?= e(t('Your account is protected with industry standard security.')) ?></small></div></li>
                <li><span><?= icon('refresh') ?></span><div><strong><?= e(t('Quick Recovery')) ?></strong><small><?= e(t('Reset your password in just a few simple steps.')) ?></small></div></li>
                <li><span><?= icon('lock') ?></span><div><strong><?= e(t('Account Protection')) ?></strong><small><?= e(t('Keep your account secure and access your rentals anytime.')) ?></small></div></li>
            </ul>
        </aside>
        <div class="auth-form">
            <span class="auth-icon"><?= icon('lock') ?></span>
            <h1><?= e(t('Forgot Password')) ?></h1>
            <form method="post" action="<?= e(url('auth/forgot')) ?>" data-ajax novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="f-email"><?= e(t('Email Address')) ?></label>
                    <div class="input-icon"><?= icon('mail') ?><input id="f-email" type="email" name="email" value="<?= e(old('email')) ?>" placeholder="<?= e(t('Enter your email address')) ?>" autocomplete="email" required></div>
                    <?= field_error('email') ?>
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('Send Reset Link')) ?><?= icon('send') ?></button>
            </form>
            <p class="auth-switch"><a href="<?= e(url('auth/login')) ?>"><?= icon('arrow-left', 'icon-xs') ?> <?= e(t('Back to Login')) ?></a></p>
        </div>
    </div>
</section>
