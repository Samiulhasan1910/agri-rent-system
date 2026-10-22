<section class="auth-section narrow">
    <div class="auth-card single">
        <div class="auth-form">
            <span class="auth-icon"><?= icon('shield-check') ?></span>
            <h1><?= e(t('Two-factor verification')) ?></h1>
            <p class="muted center"><?= e(t('Enter the 6-digit code we sent to')) ?><br><strong><?= e($email) ?></strong></p>
            <form method="post" action="<?= e(url('auth/two-factor')) ?>" data-ajax data-autosubmit-otp novalidate>
                <?= csrf_field() ?>
                <?php include __DIR__ . '/_otp.php'; ?>
                <p class="center small muted" data-expiry="<?= (int)$expiresIn ?>"><?= e(t('Code expires in')) ?> <b data-expiry-left><?= (int)$expiresIn ?>s</b></p>
                <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('Verify & Log In')) ?></button>
            </form>
            <form class="center resend-row" method="post" action="<?= e(url('auth/resend-otp')) ?>" data-ajax data-countdown-form>
                <?= csrf_field() ?>
                <span class="muted"><?= e(t("Didn't receive the code?")) ?></span>
                <button class="btn-link" type="submit" data-countdown="30"><?= e(t('Resend Code')) ?></button>
            </form>
            <p class="auth-switch"><a href="<?= e(url('auth/login')) ?>"><?= icon('arrow-left', 'icon-xs') ?> <?= e(t('Back to Login')) ?></a></p>
            <?php if (config('mail.driver') === 'log'): ?><p class="dev-note"><?= icon('info', 'icon-xs') ?> <a href="<?= e(url('mailbox/index')) ?>" target="_blank"><?= e(t('Open Mail Outbox')) ?></a></p><?php endif; ?>
        </div>
    </div>
</section>
