<section class="auth-section narrow landscape-bg">
    <div class="auth-card single">
        <div class="auth-form">
            <span class="auth-icon"><?= icon('key') ?></span>
            <h1><?= e(t('Reset Password')) ?></h1>
            <p class="muted center"><?= e(t('Enter your new password below')) ?></p>
            <form method="post" action="<?= e(url('auth/reset-password')) ?>" data-ajax novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="n-pass"><?= e(t('New Password')) ?></label>
                    <div class="input-icon"><?= icon('lock') ?><input id="n-pass" type="password" name="password" autocomplete="new-password" required data-strength><button type="button" class="pw-toggle" data-pw-toggle aria-label="<?= e(t('Show password')) ?>"><?= icon('eye') ?></button></div>
                    <small class="hint" data-strength-label><?= e(t('Use at least 8 characters with a mix of letters, numbers & symbols.')) ?></small>
                    <?= field_error('password') ?>
                </div>
                <div class="field">
                    <label for="n-pass2"><?= e(t('Confirm New Password')) ?></label>
                    <div class="input-icon"><?= icon('lock') ?><input id="n-pass2" type="password" name="password_confirmation" autocomplete="new-password" required><button type="button" class="pw-toggle" data-pw-toggle aria-label="<?= e(t('Show password')) ?>"><?= icon('eye') ?></button></div>
                    <?= field_error('password_confirmation') ?>
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('Reset Password')) ?></button>
            </form>
        </div>
    </div>
</section>
