<section class="auth-section">
    <div class="auth-card">
        <?php include __DIR__ . '/_aside.php'; ?>
        <div class="auth-form">
            <span class="auth-icon"><?= icon('user') ?></span>
            <h1><?= e(t('Welcome Back!')) ?></h1>
            <p class="muted center"><?= e(t('Log in to search, book and manage your rentals.')) ?></p>
            <form method="post" action="<?= e(url('auth/login')) ?>" data-ajax novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="email"><?= e(t('Email Address')) ?></label>
                    <div class="input-icon"><?= icon('mail') ?><input id="email" type="email" name="email" value="<?= e(old('email')) ?>" placeholder="<?= e(t('Enter your email address')) ?>" autocomplete="email" required></div>
                    <?= field_error('email') ?>
                </div>
                <div class="field">
                    <label for="password"><?= e(t('Password')) ?></label>
                    <div class="input-icon"><?= icon('lock') ?><input id="password" type="password" name="password" placeholder="<?= e(t('Enter your password')) ?>" autocomplete="current-password" required><button type="button" class="pw-toggle" data-pw-toggle aria-label="<?= e(t('Show password')) ?>"><?= icon('eye') ?></button></div>
                    <?= field_error('password') ?>
                </div>
                <div class="form-row-between">
                    <label class="check"><input type="checkbox" name="remember" value="1"> <span><?= e(t('Remember me')) ?></span></label>
                    <a href="<?= e(url('auth/forgot')) ?>"><?= e(t('Forgot Password?')) ?></a>
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('Log In')) ?></button>
            </form>
            <div class="divider"><span><?= e(t('or')) ?></span></div>
            <a class="btn btn-outline btn-block google-btn" href="<?= e(url('auth/google')) ?>"><?= icon('google') ?><?= e(t('Continue with Google')) ?></a>
            <p class="auth-switch"><?= e(t("Don't have an account?")) ?> <a href="<?= e(url('auth/register')) ?>"><?= e(t('Create Account')) ?></a></p>
            <details class="demo-box">
                <summary><?= icon('info', 'icon-xs') ?> <?= e(t('Demo accounts')) ?></summary>
                <div class="demo-grid">
                    <button type="button" data-demo-login="farmer@agrirent.test" data-demo-pass="Farmer@123"><strong><?= e(t('Farmer')) ?></strong><small>farmer@agrirent.test / Farmer@123</small></button>
                    <button type="button" data-demo-login="employee@agrirent.test" data-demo-pass="Employee@123"><strong><?= e(t('Employee')) ?></strong><small>employee@agrirent.test / Employee@123</small></button>
                    <button type="button" data-demo-login="admin@agrirent.test" data-demo-pass="Admin@123"><strong><?= e(t('Admin')) ?></strong><small>admin@agrirent.test / Admin@123</small></button>
                </div>
                <?php if (config('mail.driver') === 'log'): ?><p class="muted small"><?= e(t('Emails (verification links, OTP codes) are saved locally:')) ?> <a href="<?= e(url('mailbox/index')) ?>" target="_blank"><?= e(t('Open Mail Outbox')) ?></a></p><?php endif; ?>
            </details>
        </div>
    </div>
</section>
