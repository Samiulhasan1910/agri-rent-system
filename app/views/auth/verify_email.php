<section class="auth-section narrow">
    <div class="auth-card single">
        <div class="auth-form">
            <span class="auth-icon"><?= icon('mail') ?></span>
            <h1><?= e(t('Verify Your Email')) ?></h1>
            <?php if ($email): ?>
                <p class="muted center"><?= e(t("We've sent a 6-digit verification code and link to")) ?><br><strong><?= e($email) ?></strong></p>
                <p class="muted center small"><?= e(t('Please enter the code below to verify your email address.')) ?></p>
                <form method="post" action="<?= e(url('auth/verify-email')) ?>" data-ajax data-autosubmit-otp novalidate>
                    <?= csrf_field() ?>
                    <?php include __DIR__ . '/_otp.php'; ?>
                    <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('Verify Email')) ?></button>
                </form>
                <form class="center resend-row" method="post" action="<?= e(url('auth/resend-verification')) ?>" data-ajax data-countdown-form>
                    <?= csrf_field() ?><input type="hidden" name="email" value="<?= e($email) ?>">
                    <span class="muted"><?= e(t("Didn't receive the code?")) ?></span>
                    <button class="btn-link" type="submit" data-countdown="45"><?= e(t('Resend Code')) ?></button>
                </form>
            <?php else: ?>
                <p class="muted center"><?= e(t('Enter your email address to receive a new verification link.')) ?></p>
                <form method="post" action="<?= e(url('auth/resend-verification')) ?>" data-ajax novalidate>
                    <?= csrf_field() ?>
                    <div class="field"><div class="input-icon"><?= icon('mail') ?><input type="email" name="email" placeholder="<?= e(t('Enter your email address')) ?>" required></div><?= field_error('email') ?></div>
                    <button class="btn btn-primary btn-block" type="submit"><?= e(t('Resend verification email')) ?></button>
                </form>
            <?php endif; ?>
            <div class="divider"><span><?= e(t('or')) ?></span></div>
            <a class="btn btn-outline btn-block" href="<?= e(url('auth/register')) ?>"><?= icon('edit') ?><?= e(t('Change Email')) ?></a>
            <?php if (config('mail.driver') === 'log'): ?>
                <p class="dev-note"><?= icon('info', 'icon-xs') ?> <?= e(t('Local demo: emails are saved instead of sent.')) ?> <a href="<?= e(url('mailbox/index')) ?>" target="_blank"><?= e(t('Open Mail Outbox')) ?></a></p>
            <?php endif; ?>
        </div>
    </div>
</section>
