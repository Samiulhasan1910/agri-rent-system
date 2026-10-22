<section class="auth-section narrow">
    <div class="auth-card single">
        <div class="auth-form">
            <span class="auth-icon"><?= icon('mail') ?></span>
            <h1><?= e(t('Verify Your Email')) ?></h1>
            <p class="muted center"><?= e(t("We've sent a 6-digit verification code to")) ?><br><strong><?= e($email) ?></strong></p>
            <form method="post" action="<?= e(url('auth/reset-code')) ?>" data-ajax data-autosubmit-otp novalidate>
                <?= csrf_field() ?>
                <?php include __DIR__ . '/_otp.php'; ?>
                <p class="center small muted" data-expiry="<?= (int)$expiresIn ?>"><?= e(t('Code expires in')) ?> <b data-expiry-left><?= (int)$expiresIn ?>s</b></p>
                <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('Verify Code')) ?></button>
            </form>
            <form class="center resend-row" method="post" action="<?= e(url('auth/resend-reset')) ?>" data-ajax data-countdown-form>
                <?= csrf_field() ?>
                <span class="muted"><?= e(t("Didn't receive the code?")) ?></span>
                <button class="btn-link" type="submit" data-countdown="30"><?= e(t('Resend Code')) ?></button>
            </form>
            <p class="muted center small"><?= e(t('You can also click the reset link in the email.')) ?></p>
            <p class="auth-switch"><a href="<?= e(url('auth/forgot')) ?>"><?= icon('arrow-left', 'icon-xs') ?> <?= e(t('Use a different email')) ?></a></p>
            <?php if (config('mail.driver') === 'log'): ?><p class="dev-note"><?= icon('info', 'icon-xs') ?> <a href="<?= e(url('mailbox/index')) ?>" target="_blank"><?= e(t('Open Mail Outbox')) ?></a></p><?php endif; ?>
        </div>
    </div>
</section>
