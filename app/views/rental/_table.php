<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Farmer')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Rental Date')) ?></th><th><?= e(t('Return Date')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Payment')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r) { include __DIR__ . '/_row.php'; } ?>
        <?php if (!$rows): ?><tr><td colspan="8"><?php $icon = 'calendar'; $heading = t('No rentals found'); $text = t('Try changing the filters.'); include __DIR__ . '/../partials/empty.php'; ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
