<article class="invoice">
    <header class="invoice-head">
        <div class="brand"><?= icon('leaf') ?><span><?= e(setting('platform_name', 'AgriRent')) ?></span></div>
        <div class="right"><h1><?= e(t('INVOICE')) ?></h1><strong><?= e($p['invoice_no']) ?></strong><small class="block muted"><?= e(t('Issued')) ?> <?= e(fmt_date($p['created_at'])) ?></small></div>
    </header>
    <section class="invoice-parties">
        <div><h4><?= e(t('Billed to')) ?></h4><strong><?= e($p['farmer_name']) ?></strong><span><?= e($p['farmer_email']) ?></span><span><?= e($p['farmer_phone']) ?></span><span><?= e($p['delivery_address']) ?></span></div>
        <div><h4><?= e(t('Payment')) ?></h4><span><?= e(t('Method')) ?>: <?= e(Payment::label($p['method'])) ?></span><span><?= e(t('Transaction')) ?>: <?= e($p['transaction_id'] ?: '—') ?></span><span><?= e(t('Status')) ?>: <?= badge($p['status']) ?></span><span><?= e(t('Booking')) ?>: #<?= e($p['booking_code']) ?></span></div>
    </section>
    <table class="table invoice-table">
        <thead><tr><th><?= e(t('Description')) ?></th><th class="right"><?= e(t('Days')) ?></th><th class="right"><?= e(t('Rate')) ?></th><th class="right"><?= e(t('Amount')) ?></th></tr></thead>
        <tbody>
            <tr><td><?= e($p['equipment_name']) ?><small class="block muted"><?= e(fmt_date($p['start_date'])) ?> – <?= e(fmt_date($p['end_date'])) ?></small></td><td class="right"><?= (int)$p['days'] ?></td><td class="right"><?= money($p['price_per_day'], true) ?></td><td class="right"><?= money($p['rental_cost'], true) ?></td></tr>
            <tr><td><?= e(t('Delivery charge')) ?></td><td></td><td></td><td class="right"><?= money($p['delivery_charge'], true) ?></td></tr>
            <?php if ((float)$p['discount'] > 0): ?><tr><td><?= e(t('Discount')) ?> <?= $p['coupon_code'] ? '(' . e($p['coupon_code']) . ')' : '' ?></td><td></td><td></td><td class="right">- <?= money($p['discount'], true) ?></td></tr><?php endif; ?>
        </tbody>
        <tfoot>
            <tr><th colspan="3" class="right"><?= e(t('Booking total')) ?></th><th class="right"><?= money($p['booking_total'], true) ?></th></tr>
            <tr class="grand"><th colspan="3" class="right"><?= e($p['status'] === 'pending' ? t('Amount due') : t('Amount paid')) ?></th><th class="right"><?= money($p['amount'], true) ?></th></tr>
        </tfoot>
    </table>
    <footer class="invoice-foot muted"><?= e(t('Thank you for renting with AgriRent.')) ?> <?= e(setting('support_email')) ?> · <?= e(t('Hotline')) ?> <?= e(setting('support_phone')) ?></footer>
    <p class="no-print center"><a class="btn btn-outline btn-sm" href="<?= e(url('payment/invoice-pdf', ['id' => $p['id']])) ?>"><?= icon('download') ?><?= e(t('Download PDF')) ?></a></p>
</article>
