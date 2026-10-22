<section class="<?= Auth::check() ? '' : 'container section' ?>">
    <div class="page-head">
        <div>
            <h1><?= icon('mail') ?> <?= e(t('Mail Outbox')) ?></h1>
            <p class="muted"><?= e(t('Mail driver is set to "log": emails are stored in storage/mail instead of being sent. Configure SMTP in config/config.php for real delivery.')) ?></p>
        </div>
    </div>
    <div class="mailbox card">
        <div class="mail-list">
            <?php if (!$mails): ?>
                <?php $icon = 'mail'; $heading = t('No emails yet'); $text = t('Register or reset a password to see emails here.'); include __DIR__ . '/../partials/empty.php'; ?>
            <?php endif; ?>
            <?php foreach ($mails as $m): ?>
                <a class="mail-item <?= $m['file'] === $current ? 'active' : '' ?>" href="<?= e(url('mailbox/index', ['file' => $m['file']])) ?>">
                    <strong><?= e($m['subject']) ?></strong>
                    <small><?= e($m['to']) ?> · <?= e(fmt_datetime($m['date'])) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="mail-view">
            <?php if ($current): ?><iframe title="<?= e(t('Email preview')) ?>" sandbox="allow-popups allow-top-navigation-by-user-activation" src="<?= e(url('mailbox/show', ['file' => $current])) ?>"></iframe><?php endif; ?>
        </div>
    </div>
</section>
