<nav class="crumbs"><a href="<?= e(url('booking/index')) ?>"><?= e(t('My Bookings')) ?></a><?= icon('chevron-right', 'icon-xs') ?><a href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= e(t('Booking Details')) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e(t('Rental Cancellation')) ?></span></nav>
<div class="page-head"><div><h1><?= e(t('Rental Cancellation Request')) ?></h1></div></div>
<section class="card">
    <div class="booking-head">
        <img src="<?= e(equipment_image($b)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($b['category_icon']) ?>.svg'">
        <div class="bh-main"><h2><?= e($b['equipment_name']) ?></h2><small><?= e(t('Booking ID')) ?>: #<?= e($b['booking_code']) ?></small><?= badge($b['status']) ?></div>
        <dl class="kv kv-inline">
            <div><dt><?= e(t('Rental Period')) ?></dt><dd><?= e(fmt_date($b['start_date'])) ?> – <?= e(fmt_date($b['end_date'])) ?></dd></div>
            <div><dt><?= e(t('Total Amount')) ?></dt><dd class="text-green"><strong><?= money($b['total_amount']) ?></strong></dd></div>
            <?php if ((float)$b['paid_amount'] > 0): ?><div><dt><?= e(t('Refund')) ?></dt><dd><?= money($b['paid_amount']) ?></dd></div><?php endif; ?>
        </dl>
    </div>
    <form method="post" action="<?= e(url('booking/cancel')) ?>" data-ajax data-confirm="<?= e(t('Cancel this booking? This cannot be undone.')) ?>" novalidate>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
        <div class="field">
            <label for="reason"><?= e(t('Cancellation Reason')) ?> *</label>
            <select id="reason" name="reason" required><option value=""><?= e(t('Select a reason')) ?></option><?php foreach ($reasons as $r): ?><option value="<?= e($r) ?>"<?= selected(old('reason'), $r) ?>><?= e(t($r)) ?></option><?php endforeach; ?></select>
            <?= field_error('reason') ?>
        </div>
        <div class="field">
            <label for="details"><?= e(t('Additional Details (Optional)')) ?></label>
            <textarea id="details" name="details" rows="4" maxlength="500" placeholder="<?= e(t('Please provide more details...')) ?>" data-counter><?= e(old('details')) ?></textarea>
            <small class="hint right" data-counter-label>0/500</small>
            <?= field_error('details') ?>
        </div>
        <div class="alert alert-warning"><?= icon('alert') ?><span><?= e(t('Note: You can cancel this booking only before the delivery starts. Any payment will be refunded to the original method.')) ?></span></div>
        <div class="form-actions split">
            <a class="btn btn-outline" href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= e(t('Back')) ?></a>
            <button class="btn btn-danger" type="submit"><?= icon('trash') ?><?= e(t('Cancel Booking')) ?></button>
        </div>
    </form>
</section>
