<?php if ($error): ?><div class="alert alert-danger"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Equipment')) ?></th><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Rental Period')) ?></th><th><?= e(t('Duration')) ?></th><th><?= e(t('Final Status')) ?></th><th class="right"><?= e(t('Amount')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $b): ?>
            <tr>
                <td><a class="cell-media" href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><img class="thumb" src="<?= e(equipment_image($b)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($b['category_icon']) ?>.svg'"><strong><?= e($b['equipment_name']) ?></strong></a></td>
                <td>#<?= e($b['booking_code']) ?></td>
                <td><?= e(fmt_date($b['start_date'])) ?> – <?= e(fmt_date($b['end_date'])) ?></td>
                <td><?= (int)$b['days'] ?> <?= e(t('days')) ?></td>
                <td><?= badge(Booking::farmerStatus($b)) ?></td>
                <td class="right"><strong class="text-green"><?= money($b['total_amount']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows && !$error): ?><tr><td colspan="6"><?php $icon = 'history'; $heading = t('No rentals found'); $text = t('Try changing the filters.'); include __DIR__ . '/../partials/empty.php'; ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
