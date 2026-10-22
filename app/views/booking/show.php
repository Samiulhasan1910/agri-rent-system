<?php
$isFarmer = $user['role'] === 'farmer';
$state = $isFarmer ? Booking::farmerStatus($b) : $b['status'];
$balance = Booking::balance($b);
$pay = Booking::paymentState($b);
$steps = ['pending' => t('Requested'), 'confirmed' => t('Confirmed'), 'delivered' => t('Delivered'), 'returned' => t('Returned'), 'completed' => t('Completed')];
$order = array_keys($steps);
$pos = array_search($b['status'], $order, true);
?>
<nav class="crumbs"><a href="<?= e(url($isFarmer ? 'booking/index' : 'rental/index')) ?>"><?= e($isFarmer ? t('My Bookings') : t('Rentals')) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e(t('Booking Details')) ?></span></nav>
<div class="page-head">
    <div><h1><?= e(t('Booking Details')) ?></h1><p class="muted">#<?= e($b['booking_code']) ?> · <?= e(t('Booked on')) ?> <?= e(fmt_datetime($b['created_at'])) ?></p></div>
    <div class="row-actions">
        <?php if ($isFarmer && Booking::canExtend($b)): ?><a class="btn btn-outline" href="<?= e(url('booking/extend', ['id' => $b['id']])) ?>"><?= icon('calendar') ?><?= e(t('Extend Rental')) ?></a><?php endif; ?>
        <?php if ($isFarmer && Booking::canCancel($b)): ?><a class="btn btn-danger-soft" href="<?= e(url('booking/cancel', ['id' => $b['id']])) ?>"><?= icon('x') ?><?= e(t('Cancel Booking')) ?></a><?php endif; ?>
        <?php if ($isFarmer && $balance > 0 && !in_array($b['status'], ['cancelled', 'rejected'], true)): ?><a class="btn btn-primary" href="<?= e(url('payment/checkout', ['booking' => $b['id']])) ?>"><?= icon('credit-card') ?><?= e(t('Pay')) ?> <?= money($balance) ?></a><?php endif; ?>
    </div>
</div>

<?php if ($b['pending_extension_id']): ?><div class="alert alert-info"><?= icon('clock') ?><span><?= e(t('Your extension request is waiting for approval.')) ?></span></div><?php endif; ?>
<?php if ($b['status'] === 'rejected' && $b['status_note']): ?><div class="alert alert-danger"><?= icon('x-circle') ?><span><?= e(t('Rejected')) ?>: <?= e($b['status_note']) ?></span></div><?php endif; ?>
<?php if ($b['status'] === 'cancelled'): ?><div class="alert alert-warning"><?= icon('info') ?><span><?= e(t('Cancelled on :date. Reason: :reason', ['date' => fmt_datetime($b['cancelled_at']), 'reason' => t((string)$b['cancellation_reason'])])) ?> <?= e((string)$b['cancellation_details']) ?></span></div><?php endif; ?>

<section class="card">
    <div class="booking-head">
        <img src="<?= e(equipment_image($b)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($b['category_icon']) ?>.svg'">
        <div class="bh-main">
            <h2><?= e($b['equipment_name']) ?></h2>
            <?= badge($state) ?>
            <small><?= e(t('Booking ID')) ?>: #<?= e($b['booking_code']) ?></small>
            <?php if ($b['owner_name']): ?><small><?= icon('user', 'icon-xs') ?><?= e($b['owner_name']) ?><?php if ($b['owner_phone']): ?> · <?= icon('phone', 'icon-xs') ?><?= e($b['owner_phone']) ?><?php endif; ?></small><?php endif; ?>
        </div>
        <dl class="kv kv-inline">
            <div><dt><?= icon('calendar', 'icon-xs') ?><?= e(t('Booking Date')) ?></dt><dd><?= e(fmt_date($b['created_at'])) ?></dd></div>
            <div><dt><?= icon('calendar', 'icon-xs') ?><?= e(t('Rental Period')) ?></dt><dd><?= e(fmt_date($b['start_date'])) ?> – <?= e(fmt_date($b['end_date'])) ?> (<?= (int)$b['days'] ?> <?= e(t('days')) ?>)</dd></div>
            <div><dt><?= icon('banknote', 'icon-xs') ?><?= e(t('Total Amount')) ?></dt><dd class="text-green"><strong><?= money($b['total_amount']) ?></strong></dd></div>
            <div><dt><?= icon('credit-card', 'icon-xs') ?><?= e(t('Payment Status')) ?></dt><dd><?= badge($pay) ?></dd></div>
        </dl>
    </div>
    <?php if (!in_array($b['status'], ['cancelled', 'rejected'], true)): ?>
        <ol class="progress-steps">
            <?php foreach ($steps as $k => $label): $i = array_search($k, $order, true); ?>
                <li class="<?= $pos !== false && $i < $pos ? 'done' : ($i === $pos ? 'current' : '') ?>"><span><?= $pos !== false && $i < $pos ? icon('check', 'icon-xs') : $i + 1 ?></span><?= e($label) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
    <div class="info-grid">
        <div><small><?= icon('calendar', 'icon-xs') ?><?= e(t('Current Return Date')) ?></small><strong><?= e(fmt_date($b['end_date'])) ?></strong></div>
        <div><small><?= icon('truck', 'icon-xs') ?><?= e(t('Delivery Date')) ?></small><strong><?= e(fmt_date($b['scheduled_date'] ?: $b['start_date'])) ?><?= $b['estimated_time'] ? ' · ' . e($b['estimated_time']) : '' ?></strong></div>
        <div><small><?= icon('map-pin', 'icon-xs') ?><?= e(t('Equipment Location')) ?></small><strong><?= e($b['equipment_location']) ?></strong></div>
        <div><small><?= icon('map-pin', 'icon-xs') ?><?= e(t('Delivery Address')) ?></small><strong><?= e($b['delivery_address']) ?></strong></div>
    </div>
</section>

<div class="grid-2-1">
    <div>
        <section class="card">
            <div class="card-head"><h2><?= e(t('Delivery Tracking')) ?></h2><?= badge($b['delivery_status'] ?? 'pending') ?></div>
            <div class="progress-bar" aria-label="<?= e(t('Delivery progress')) ?>"><span style="width:<?= (int)($b['delivery_progress'] ?? 0) ?>%"></span></div>
            <?php if ($b['team_name']): ?><p class="muted small"><?= icon('truck', 'icon-xs') ?> <?= e($b['team_name']) ?> · <?= e($b['team_leader']) ?> · <a href="tel:<?= e($b['team_phone']) ?>"><?= e($b['team_phone']) ?></a></p><?php endif; ?>
            <ul class="timeline">
                <li class="done"><b><?= e(t('Booking requested')) ?></b><small><?= e(fmt_datetime($b['created_at'])) ?></small></li>
                <?php foreach ($updates as $u): ?><li class="done"><b><?= e(status_meta($u['status'])[0]) ?></b><small><?= e(fmt_datetime($u['created_at'])) ?><?= $u['note'] ? ' · ' . e($u['note']) : '' ?></small></li><?php endforeach; ?>
                <?php if (!$updates): ?><li><b><?= e(t('Waiting for delivery assignment')) ?></b></li><?php endif; ?>
            </ul>
        </section>

        <?php if ($isFarmer && $b['status'] === 'completed'): ?>
            <section class="card" id="review">
                <h2 class="card-title"><?= e(t('Rate this equipment')) ?></h2>
                <?php if ($b['review_id']): ?>
                    <p><?= stars((float)$b['review_rating']) ?> <span class="muted"><?= e(t('Thank you for your review!')) ?></span></p>
                <?php else: ?>
                    <form method="post" action="<?= e(url('review/store')) ?>" data-ajax data-replace-self novalidate>
                        <?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                        <div class="field"><label><?= e(t('1. Select rating')) ?></label>
                            <div class="star-input" role="radiogroup" aria-label="<?= e(t('Rating')) ?>">
                                <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>"><label for="star<?= $i ?>" title="<?= $i ?>">★</label><?php endfor; ?>
                            </div><?= field_error('rating') ?></div>
                        <div class="field"><label for="rv"><?= e(t('2. Write your review')) ?></label><textarea id="rv" name="review" rows="3" maxlength="1000" placeholder="<?= e(t('Share your experience with this equipment...')) ?>"></textarea><?= field_error('review') ?></div>
                        <button class="btn btn-primary" type="submit"><?= e(t('3. Submit Review')) ?></button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($extensions): ?>
            <section class="card">
                <h2 class="card-title"><?= e(t('Extension Requests')) ?></h2>
                <div class="table-wrap"><table class="table compact">
                    <thead><tr><th><?= e(t('Requested')) ?></th><th><?= e(t('New Return Date')) ?></th><th><?= e(t('Extra')) ?></th><th><?= e(t('Status')) ?></th></tr></thead>
                    <tbody><?php foreach ($extensions as $x): ?><tr><td><?= e(fmt_date($x['created_at'])) ?></td><td><?= e(fmt_date($x['requested_end_date'])) ?></td><td><?= (int)$x['extra_days'] ?> <?= e(t('days')) ?> · <?= money($x['extra_cost']) ?></td><td><?= badge($x['status']) ?><?php if ($x['response_note']): ?><small class="block muted"><?= e($x['response_note']) ?></small><?php endif; ?></td></tr><?php endforeach; ?></tbody>
                </table></div>
            </section>
        <?php endif; ?>
    </div>

    <aside>
        <section class="card">
            <h2 class="card-title"><?= e(t('Payment Summary')) ?></h2>
            <dl class="kv">
                <div><dt><?= e(t('Base Amount')) ?> (<?= (int)$b['days'] ?> <?= e(t('days')) ?>)</dt><dd><?= money($b['rental_cost']) ?></dd></div>
                <div><dt><?= e(t('Delivery Charge')) ?></dt><dd><?= money($b['delivery_charge']) ?></dd></div>
                <?php if ((float)$b['discount'] > 0): ?><div><dt><?= e(t('Discount')) ?></dt><dd>- <?= money($b['discount']) ?></dd></div><?php endif; ?>
                <div class="kv-total"><dt><?= e(t('Total Cost')) ?></dt><dd><?= money($b['total_amount']) ?></dd></div>
                <div><dt><?= e(t('Paid')) ?></dt><dd><?= money($b['paid_amount']) ?></dd></div>
                <?php if ((float)$b['pending_amount'] > 0): ?><div><dt><?= e(t('Cash on delivery')) ?></dt><dd><?= money($b['pending_amount']) ?></dd></div><?php endif; ?>
                <div><dt><?= e(t('Balance Due')) ?></dt><dd class="<?= $balance > 0 ? 'text-danger' : 'text-green' ?>"><strong><?= money($balance) ?></strong></dd></div>
            </dl>
            <?php foreach ($payments as $p): ?>
                <div class="pay-row"><span><?= e($p['invoice_no']) ?> · <?= e(Payment::label($p['method'])) ?></span><?= badge($p['status']) ?>
                    <?php if (in_array($p['status'], ['paid', 'pending', 'refunded'], true)): ?><a class="btn-link" href="<?= e(url('payment/invoice', ['id' => $p['id']])) ?>"><?= e(t('Invoice')) ?></a><?php endif; ?></div>
            <?php endforeach; ?>
        </section>

        <?php if ($isFarmer): ?>
            <section class="card help-card">
                <?= icon('headset') ?><div><strong><?= e(t('Need Help?')) ?></strong><small><?= e(t('If you have any questions or face any issue contact our support team.')) ?></small></div>
                <?php if ($contact): ?><a class="btn btn-primary btn-sm btn-block" href="<?= e(url('chat/index', ['with' => $contact['id']])) ?>"><?= icon('message') ?><?= e(t('Chat with Employee')) ?></a><?php endif; ?>
                <a class="btn btn-light btn-sm btn-block" href="<?= e(url('complaint/index', ['booking' => $b['id']])) ?>"><?= icon('alert') ?><?= e(t('Report an issue')) ?></a>
            </section>
        <?php else: ?>
            <section class="card">
                <h2 class="card-title"><?= e(t('Farmer')) ?></h2>
                <p><strong><?= e($b['farmer_name']) ?></strong><br><small class="muted"><?= e($b['farmer_email']) ?> · <?= e($b['farmer_phone']) ?></small></p>
                <a class="btn btn-light btn-sm" href="<?= e(url('chat/index', ['with' => $b['farmer_id']])) ?>"><?= icon('message') ?><?= e(t('Message farmer')) ?></a>
                <?php $next = Booking::TRANSITIONS[$b['status']] ?? []; if ($next): ?>
                    <h3 class="sub-title"><?= e(t('Update status')) ?></h3>
                    <div class="row-actions">
                        <?php foreach ($next as $to): ?>
                            <form method="post" action="<?= e(url('rental/status')) ?>" data-ajax data-reload <?= $to === 'rejected' ? 'data-prompt="' . e(t('Reason for rejection')) . '" data-prompt-field="note"' : '' ?>>
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="status" value="<?= $to ?>">
                                <button class="btn <?= $to === 'rejected' ? 'btn-danger-soft' : 'btn-primary' ?> btn-sm" type="submit"><?= e(['confirmed' => t('Confirm'), 'rejected' => t('Reject'), 'delivered' => t('Mark Delivered'), 'returned' => t('Mark Returned')][$to]) ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($inspection): ?><p class="muted small"><?= e(t('Inspection')) ?>: <?= badge($inspection['condition']) ?> · <?= e($inspection['inspector_name']) ?></p><?php elseif ($b['status'] === 'returned'): ?><a class="btn btn-outline btn-sm" href="<?= e(url('inspection/index')) ?>"><?= e(t('Inspect return')) ?></a><?php endif; ?>
            </section>
        <?php endif; ?>
    </aside>
</div>
<a class="back-link" href="<?= e(url($isFarmer ? 'booking/index' : 'rental/index')) ?>"><?= icon('arrow-left', 'icon-xs') ?><?= e($isFarmer ? t('Back to My Bookings') : t('Back to Rentals')) ?></a>
