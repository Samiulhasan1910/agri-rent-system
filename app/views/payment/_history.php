<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Date & Time')) ?></th><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Transaction ID')) ?></th><th class="right"><?= e(t('Amount')) ?></th><th><?= e(t('Payment Method')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Invoice')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
            <tr>
                <td><?= e(fmt_datetime($p['created_at'])) ?></td>
                <td><a href="<?= e(url('booking/show', ['id' => $p['booking_id']])) ?>">#<?= e($p['booking_code']) ?></a></td>
                <td><?= e($p['transaction_id'] ?: '—') ?></td>
                <td class="right"><strong><?= money($p['amount']) ?></strong></td>
                <td><span class="method method-<?= e($p['method']) ?>"><?= e(Payment::label($p['method'])) ?></span></td>
                <td><?= badge($p['status']) ?><?php if ($p['failure_reason']): ?><small class="block text-danger"><?= e($p['failure_reason']) ?></small><?php endif; ?></td>
                <td class="nowrap">
                    <?php if (in_array($p['status'], ['paid', 'pending', 'refunded'], true)): ?>
                        <a class="btn btn-light btn-xs" href="<?= e(url('payment/invoice-pdf', ['id' => $p['id']])) ?>"><?= icon('download', 'icon-xs') ?><?= e(t('Download')) ?></a>
                    <?php elseif ($p['status'] === 'failed' && !in_array($p['booking_status'], ['cancelled', 'rejected'], true)): ?>
                        <a class="btn btn-outline btn-xs" href="<?= e(url('payment/checkout', ['booking' => $p['booking_id']])) ?>"><?= icon('refresh', 'icon-xs') ?><?= e(t('Retry')) ?></a>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7"><?php $icon = 'receipt'; $heading = t('No payments found'); $text = t('Payments you make will appear here.'); include __DIR__ . '/../partials/empty.php'; ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
