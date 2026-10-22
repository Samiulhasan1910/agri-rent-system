<ol class="stepper">
    <li class="done"><span><?= icon('check', 'icon-xs') ?></span><?= e(t('Booking Details')) ?></li>
    <li class="<?= ($step ?? 2) > 2 ? 'done' : 'current' ?>"><span><?= ($step ?? 2) > 2 ? icon('check', 'icon-xs') : '2' ?></span><?= e(t('Payment Method')) ?></li>
    <li class="<?= ($step ?? 2) >= 3 ? 'current' : '' ?>"><span>3</span><?= e(t('Confirmation')) ?></li>
</ol>
