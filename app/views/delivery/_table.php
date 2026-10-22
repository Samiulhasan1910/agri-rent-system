<div class="table-wrap">
    <table class="table selectable">
        <thead><tr><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Farmer')) ?></th><th><?= e(t('Address')) ?></th><th><?= e(t('Delivery Team')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r) { include __DIR__ . '/_row.php'; } ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="center muted"><?= e(t('No deliveries found')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
