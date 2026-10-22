<div class="toast-stack" id="toasts" aria-live="polite" aria-atomic="false">
    <?php foreach (Session::flashes() as [$type, $message]): ?>
        <div class="toast toast-<?= e($type) ?>" role="status">
            <?= icon(['success' => 'check-circle', 'error' => 'x-circle', 'warning' => 'alert', 'info' => 'info'][$type] ?? 'info') ?>
            <span><?= e($message) ?></span>
            <button type="button" class="toast-close" aria-label="<?= e(t('Close')) ?>">×</button>
        </div>
    <?php endforeach; ?>
</div>
