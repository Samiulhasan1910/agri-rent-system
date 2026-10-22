<section class="card success-card">
    <span class="success-icon"><?= icon('check-circle') ?></span>
    <h1><?= e(t('Booking Cancelled Successfully!')) ?></h1>
    <p class="muted"><?= e(t('Your booking has been cancelled.')) ?></p>
    <div class="booking-head compact">
        <img src="<?= e(equipment_image($b)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($b['category_icon']) ?>.svg'">
        <div class="bh-main"><h2><?= e($b['equipment_name']) ?></h2><small><?= e(t('Booking ID')) ?>: #<?= e($b['booking_code']) ?></small></div>
        <dl class="kv kv-inline">
            <div><dt><?= e(t('Rental Period')) ?></dt><dd><?= e(fmt_date($b['start_date'])) ?> – <?= e(fmt_date($b['end_date'])) ?></dd></div>
            <div><dt><?= e(t('Total Amount')) ?></dt><dd><?= money($b['total_amount']) ?></dd></div>
            <div><dt><?= e(t('Status')) ?></dt><dd><?= badge('cancelled') ?></dd></div>
        </dl>
    </div>
    <?php if ((int)$b['refunded_count'] > 0): ?><div class="alert alert-success"><?= icon('banknote') ?><span><?= e(t('A refund has been initiated to your original payment method.')) ?></span></div><?php endif; ?>
    <div class="alert alert-info"><?= icon('info') ?><span><?= e(t('The cancellation is saved in your rental history and the admin has been notified.')) ?></span></div>
    <div class="stack narrow-stack">
        <a class="btn btn-primary btn-block" href="<?= e(url('booking/history')) ?>"><?= e(t('View History')) ?><?= icon('arrow-right') ?></a>
        <a class="btn btn-outline btn-block" href="<?= e(url('dashboard/index')) ?>"><?= icon('home') ?><?= e(t('Back to Home')) ?></a>
    </div>
</section>
