<div class="empty-state">
    <span class="empty-icon"><?= icon($icon ?? 'package') ?></span>
    <h3><?= e($heading ?? t('Nothing here yet')) ?></h3>
    <?php if (!empty($text)): ?><p><?= e($text) ?></p><?php endif; ?>
    <?= $action ?? '' ?>
</div>
