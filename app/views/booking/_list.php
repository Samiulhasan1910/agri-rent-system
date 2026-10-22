<div class="booking-list">
    <?php foreach ($rows as $b): $state = Booking::farmerStatus($b); $pay = Booking::paymentState($b); ?>
        <article class="booking-card">
            <img src="<?= e(equipment_image($b)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($b['category_icon']) ?>.svg'">
            <div class="bc-main">
                <h3><?= e($b['equipment_name']) ?></h3>
                <small><?= icon('calendar', 'icon-xs') ?><?= e(t('Rental Period')) ?>: <?= e(fmt_date($b['start_date'])) ?> – <?= e(fmt_date($b['end_date'])) ?></small>
                <small><?= icon('map-pin', 'icon-xs') ?><?= e($b['delivery_address']) ?></small>
            </div>
            <div class="bc-col"><small><?= e(t('Booking ID')) ?></small><strong>#<?= e($b['booking_code']) ?></strong></div>
            <div class="bc-col"><small><?= e(t('Total Amount')) ?></small><strong class="text-green"><?= money($b['total_amount']) ?></strong><?= badge($pay) ?></div>
            <div class="bc-actions">
                <?= badge($state) ?>
                <a class="btn btn-outline btn-sm" href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= e(t('View Details')) ?><?= icon('chevron-right', 'icon-xs') ?></a>
                <?php if (Booking::balance($b) > 0 && !in_array($b['status'], ['cancelled', 'rejected'], true) && $pay !== 'pending'): ?>
                    <a class="btn btn-primary btn-sm" href="<?= e(url('payment/checkout', ['booking' => $b['id']])) ?>"><?= e(t('Pay')) ?> <?= money(Booking::balance($b)) ?></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <?php $icon = 'calendar'; $heading = t('No bookings found'); $text = t('Bookings you make will appear here.'); $action = '<a class="btn btn-primary btn-sm" href="' . e(url('equipment/index')) . '">' . e(t('Browse Equipment')) . '</a>'; include __DIR__ . '/../partials/empty.php'; unset($action); ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
