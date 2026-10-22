<?php $cash = $p['method'] === 'cash'; $awaiting = $b && $b['status'] === 'pending'; ?>
<?php $step = 3; include __DIR__ . '/_stepper.php'; ?>
<section class="card success-card confetti">
    <span class="success-icon"><?= icon('check-circle') ?></span>
    <h1><?= e($cash ? t('Booking Confirmed!') : t('Payment Successful!')) ?></h1>
    <p class="muted"><?= e($cash ? t('Your Cash on Delivery order has been confirmed.') : t('Your payment has been completed successfully.')) ?> <?= e(t('Thank you for using :name.', ['name' => setting('platform_name', 'AgriRent')])) ?></p>
    <div class="badge badge-green badge-lg"><?= icon('shield-check', 'icon-xs') ?><?= e($cash ? t('Your booking is confirmed and secure.') : t('Your transaction is secure and complete.')) ?></div>

    <div class="sub-card receipt">
        <h3><?= icon('receipt') ?><?= e(t('Payment Details')) ?></h3>
        <dl class="kv">
            <?php if (!$cash): ?><div><dt><?= e(t('Transaction ID')) ?></dt><dd><?= e($p['transaction_id']) ?></dd></div><?php endif; ?>
            <div><dt><?= e(t('Invoice')) ?></dt><dd><?= e($p['invoice_no']) ?></dd></div>
            <div><dt><?= e(t('Booking ID')) ?></dt><dd>#<?= e($p['booking_code']) ?></dd></div>
            <div><dt><?= e(t('Payment Method')) ?></dt><dd><?= e(Payment::label($p['method'])) ?><?= $p['account_ref'] ? ' · ' . e($p['account_ref']) : '' ?></dd></div>
            <?php if ($cash): ?><div><dt><?= e(t('Pay When')) ?></dt><dd><?= e(t('On delivery, when you receive the equipment')) ?></dd></div><?php endif; ?>
            <?php if ((float)$p['discount'] > 0): ?><div><dt><?= e(t('Discount')) ?> (<?= e($p['coupon_code']) ?>)</dt><dd>- <?= money($p['discount'], true) ?></dd></div><?php endif; ?>
            <div><dt><?= e($cash ? t('Amount to Pay') : t('Paid Amount')) ?></dt><dd class="text-green"><strong><?= money($p['amount'], true) ?></strong></dd></div>
            <div><dt><?= e($cash ? t('Delivery Date') : t('Payment Date')) ?></dt><dd><?= e($cash ? fmt_date($p['start_date']) : fmt_datetime($p['paid_at'] ?? $p['created_at'])) ?></dd></div>
            <div><dt><?= e(t('Payment Status')) ?></dt><dd><?= badge($p['status']) ?></dd></div>
        </dl>
    </div>

    <div class="success-strip">
        <div><?= icon('calendar-check') ?><div><strong><?= e($awaiting ? t('Waiting for Confirmation') : t('Booking Confirmed')) ?></strong><small><?= e($awaiting ? t('An employee will approve your booking shortly.') : t('Your booking has been confirmed successfully.')) ?></small></div></div>
        <div><?= icon('truck') ?><div><strong><?= e(t('Delivery on :date', ['date' => fmt_date($p['start_date'])])) ?></strong><small><?= e(t('We will deliver the equipment on your selected date.')) ?></small></div></div>
        <div><?= icon('phone') ?><div><strong><?= e(t('Need Help?')) ?></strong><small><?= e(setting('support_phone')) ?></small></div></div>
    </div>
    <div class="row-actions center">
        <a class="btn btn-outline" href="<?= e(url('payment/invoice', ['id' => $p['id']])) ?>"><?= icon('printer') ?><?= e(t('View Invoice')) ?></a>
        <a class="btn btn-outline" href="<?= e(url('payment/invoice-pdf', ['id' => $p['id']])) ?>"><?= icon('download') ?><?= e(t('Download PDF')) ?></a>
        <a class="btn btn-primary" href="<?= e(url('booking/show', ['id' => $p['booking_id']])) ?>"><?= e(t('View Booking')) ?><?= icon('arrow-right') ?></a>
    </div>
</section>

<?php if ($awaiting): ?>
<div class="modal" id="pendingModal" role="dialog" aria-modal="true" data-auto-open>
    <div class="modal-card modal-sm center">
        <button type="button" class="btn-icon modal-x" data-modal-close aria-label="<?= e(t('Close')) ?>">×</button>
        <span class="success-icon sm"><?= icon('receipt') ?></span>
        <h3><?= e($cash ? t('Order Submitted') : t('Payment Submitted')) ?></h3>
        <p class="muted"><?= e(t('Your payment is under review')) ?></p>
        <span class="badge badge-amber badge-lg"><?= icon('clock', 'icon-xs') ?><?= e(t('Status: Pending Approval')) ?></span>
        <p class="muted small"><?= icon('bell', 'icon-xs') ?> <?= e(t('We will notify you once your booking is approved.')) ?></p>
        <a class="btn btn-primary btn-block" href="<?= e(url('payment/history')) ?>"><?= e(t('View Payment Status')) ?></a>
    </div>
</div>
<?php endif; ?>
