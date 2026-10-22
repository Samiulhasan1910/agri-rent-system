<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Invoice ID')) ?></th><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Farmer')) ?></th><th class="right"><?= e(t('Amount')) ?></th><th><?= e(t('Method')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
            <tr>
                <td><strong><?= e($p['invoice_no']) ?></strong><?php if ($p['invoice_generated_at']): ?><small class="block muted"><?= icon('check', 'icon-xs') ?><?= e(t('Invoice generated')) ?></small><?php endif; ?></td>
                <td><a href="<?= e(url('booking/show', ['id' => $p['booking_id']])) ?>"><?= e($p['booking_code']) ?></a></td>
                <td><?= e($p['farmer_name']) ?></td>
                <td class="right"><?= money($p['amount']) ?></td>
                <td><span class="method method-<?= e($p['method']) ?>"><?= e(Payment::label($p['method'])) ?></span></td>
                <td><?= badge($p['status']) ?><?php if ($p['failure_reason']): ?><small class="block text-danger"><?= e($p['failure_reason']) ?></small><?php endif; ?></td>
                <td><?= e(fmt_date($p['created_at'])) ?></td>
                <td class="nowrap">
                    <?php if ($p['status'] === 'failed'): ?>
                        <form class="inline" method="post" action="<?= e(url('payment/remind')) ?>" data-ajax><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-danger-soft btn-xs"><?= icon('refresh', 'icon-xs') ?><?= e(t('Retry')) ?></button></form>
                    <?php elseif ($p['status'] === 'pending' && $p['method'] === 'cash'): ?>
                        <form class="inline" method="post" action="<?= e(url('payment/mark-paid')) ?>" data-ajax data-reload data-confirm="<?= e(t('Mark this cash payment as received?')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-primary btn-xs"><?= icon('check', 'icon-xs') ?><?= e(t('Mark paid')) ?></button></form>
                    <?php endif; ?>
                    <?php if (in_array($p['status'], ['paid', 'pending', 'refunded'], true)): ?>
                        <?php if (!$p['invoice_generated_at']): ?>
                            <form class="inline" method="post" action="<?= e(url('payment/generate-invoice')) ?>" data-ajax data-reload><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-outline btn-xs"><?= icon('file-text', 'icon-xs') ?><?= e(t('Generate')) ?></button></form>
                        <?php else: ?>
                            <a class="btn btn-light btn-xs" href="<?= e(url('payment/invoice-pdf', ['id' => $p['id']])) ?>"><?= icon('download', 'icon-xs') ?><?= e(t('Download')) ?></a>
                        <?php endif; ?>
                        <a class="btn btn-light btn-xs" href="<?= e(url('payment/invoice', ['id' => $p['id']])) ?>" target="_blank"><?= e(t('View')) ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="center muted"><?= e(t('No payments found')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
