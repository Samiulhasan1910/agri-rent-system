<?php
$next = Booking::TRANSITIONS[$r['status']] ?? [];
$labels = ['confirmed' => t('Accept'), 'rejected' => t('Reject'), 'delivered' => t('Mark Delivered'), 'returned' => t('Mark Returned')];
?>
<tr data-row="booking-<?= (int)$r['id'] ?>" data-booking-status="<?= e($r['status']) ?>">
    <td><strong><?= e($r['booking_code']) ?></strong></td>
    <td><?= e($r['farmer_name']) ?><small class="block muted"><?= e($r['farmer_phone']) ?></small></td>
    <td><?= e($r['equipment_name']) ?></td>
    <td><?= e(fmt_date($r['start_date'])) ?></td>
    <td><?= e(fmt_date($r['end_date'])) ?><?php if ($r['pending_extension_id']): ?><small class="block text-blue"><?= e(t('Extension requested')) ?></small><?php endif; ?></td>
    <td data-status-cell><?= badge($r['status']) ?></td>
    <td><?= badge(Booking::paymentState($r)) ?></td>
    <td class="nowrap">
        <?php foreach ($next as $to): ?>
            <form class="inline" method="post" action="<?= e(url('rental/status')) ?>" data-ajax data-replace-row="booking-<?= (int)$r['id'] ?>" <?= $to === 'rejected' ? 'data-prompt="' . e(t('Reason for rejection')) . '" data-prompt-field="note"' : '' ?>>
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="<?= $to ?>">
                <button class="btn <?= $to === 'rejected' ? 'btn-danger-soft' : 'btn-primary' ?> btn-xs" type="submit"><?= e($labels[$to]) ?></button>
            </form>
        <?php endforeach; ?>
        <a class="btn btn-light btn-xs" href="<?= e(url('booking/show', ['id' => $r['id']])) ?>"><?= e(t('View')) ?></a>
    </td>
</tr>
