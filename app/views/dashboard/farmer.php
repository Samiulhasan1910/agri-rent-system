<section class="card search-card">
    <h2 class="card-title"><?= e(t('Search Equipment')) ?></h2>
    <form class="search-bar" method="get" action="index.php">
        <input type="hidden" name="r" value="equipment/index">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" placeholder="<?= e(t('Search tractor, pump, tiller...')) ?>" aria-label="<?= e(t('Search')) ?>"></div>
        <select name="category" aria-label="<?= e(t('Category')) ?>"><option value=""><?= e(t('Category')) ?></option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e(t($c['name'])) ?></option><?php endforeach; ?></select>
        <select name="location" aria-label="<?= e(t('Location')) ?>"><option value=""><?= e(t('Location')) ?></option><?php foreach ($locations as $l): ?><option><?= e($l) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit"><?= icon('search') ?><?= e(t('Search')) ?></button>
    </form>
    <h3 class="sub-title"><?= e(t('Browse by Category')) ?></h3>
    <div class="category-row compact">
        <?php foreach ($categories as $c): ?>
            <a class="category-tile" href="<?= e(url('equipment/index', ['category' => $c['id']])) ?>"><img src="assets/img/equipment/<?= e($c['icon']) ?>.svg" alt="" loading="lazy"><strong><?= e(t($c['name'])) ?></strong></a>
        <?php endforeach; ?>
    </div>
</section>

<section class="stats">
    <article class="stat"><span class="stat-icon"><?= icon('calendar-check') ?></span><div><strong><?= (int)$active ?></strong><span><?= e(t('Active Rentals')) ?></span></div></article>
    <article class="stat"><span class="stat-icon amber"><?= icon('clock') ?></span><div><strong><?= (int)$counts['pending'] ?></strong><span><?= e(t('Pending Requests')) ?></span></div></article>
    <article class="stat"><span class="stat-icon red"><?= icon('heart') ?></span><div><strong><?= (int)$wishlistCount ?></strong><span><?= e(t('Saved Equipment')) ?></span></div></article>
    <article class="stat"><span class="stat-icon blue"><?= icon('wallet') ?></span><div><strong><?= money($spent) ?></strong><span><?= e(t('Total Paid')) ?></span></div></article>
</section>

<div class="grid-2-1">
    <section class="card">
        <div class="card-head"><h2><?= e(t('Latest Booking')) ?></h2><a class="btn btn-primary btn-sm" href="<?= e(url('booking/index')) ?>"><?= e(t('View Bookings')) ?></a></div>
        <?php if ($latest): ?>
            <div class="latest-booking">
                <img src="<?= e(equipment_image($latest)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($latest['category_icon']) ?>.svg'">
                <div class="lb-info">
                    <strong><?= e($latest['equipment_name']) ?></strong>
                    <small><?= e(t('Booking ID')) ?>: #<?= e($latest['booking_code']) ?></small>
                    <small><?= icon('map-pin', 'icon-xs') ?><?= e($latest['delivery_address']) ?></small>
                </div>
                <div class="lb-col"><small><?= e(t('Status')) ?></small><?= badge(Booking::farmerStatus($latest)) ?></div>
                <div class="lb-col"><small><?= e(t('From - To')) ?></small><span><?= e(fmt_date($latest['start_date'])) ?></span><span><?= e(fmt_date($latest['end_date'])) ?></span></div>
                <a class="btn btn-outline btn-sm" href="<?= e(url('booking/show', ['id' => $latest['id']])) ?>"><?= e(t('Details')) ?></a>
            </div>
        <?php else: ?>
            <?php $icon = 'calendar'; $heading = t('No bookings yet'); $text = t('Find the right machine and make your first booking.'); $action = '<a class="btn btn-primary btn-sm" href="' . e(url('equipment/index')) . '">' . e(t('Browse Equipment')) . '</a>'; include __DIR__ . '/../partials/empty.php'; unset($action); ?>
        <?php endif; ?>

        <h3 class="sub-title"><?= icon('bell', 'icon-xs') ?> <?= e(t('Important Notice')) ?></h3>
        <div class="notice-grid">
            <div class="notice">
                <span class="notice-icon"><?= icon('calendar') ?></span>
                <div><strong><?= e(t('Return Date Reminder')) ?></strong>
                    <?php if ($endingSoon): $r = $endingSoon[0]; ?>
                        <p><?= e(t('Your :equipment rental will end on :date.', ['equipment' => $r['equipment_name'], 'date' => fmt_date($r['end_date'])])) ?></p>
                        <a href="<?= e(url('booking/extend', ['id' => $r['id']])) ?>"><?= e(t('Need more days? Extend')) ?></a>
                    <?php else: ?><p><?= e(t('No rentals end in the next 2 days.')) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="notice">
                <span class="notice-icon amber"><?= icon('credit-card') ?></span>
                <div><strong><?= e(t('Payment Reminder')) ?></strong>
                    <?php if ($dues): $d = $dues[0]; ?>
                        <p><?= e(t(':amount is due for :code.', ['amount' => money(Booking::balance($d)), 'code' => $d['booking_code']])) ?></p>
                        <a href="<?= e(url('payment/checkout', ['booking' => $d['id']])) ?>"><?= e(t('Pay now')) ?></a>
                    <?php else: ?><p class="ok"><?= icon('check-circle', 'icon-xs') ?> <?= e(t('No pending payment. Thank you!')) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="notice">
                <span class="notice-icon blue"><?= icon('headset') ?></span>
                <div><strong><?= e(t('Need Help?')) ?></strong><p><?= e(t('Contact our support team.')) ?></p>
                    <a class="btn btn-outline btn-xs" href="<?= e(url('chat/index')) ?>"><?= e(t('Contact Support')) ?></a></div>
            </div>
        </div>
    </section>

    <section class="card quick-card">
        <div class="card-head"><h2><?= e(t('Quick Actions')) ?></h2></div>
        <a class="quick-link" href="<?= e(url('smart/index')) ?>"><span class="ql-icon"><?= icon('sparkles') ?></span><div><strong><?= e(t('Smart Equipment Match')) ?></strong><small><?= e(t('Get suggestions by crop, season & farm size')) ?></small></div><?= icon('chevron-right') ?></a>
        <a class="quick-link danger" href="<?= e(url('emergency/index')) ?>"><span class="ql-icon"><?= icon('siren') ?></span><div><strong><?= e(t('Emergency Request')) ?></strong><small><?= e(t('Find the nearest available machine now')) ?></small></div><?= icon('chevron-right') ?></a>
        <a class="quick-link" href="<?= e(url('booking/history')) ?>"><span class="ql-icon"><?= icon('file-text') ?></span><div><strong><?= e(t('Rental History Report')) ?></strong><small><?= e(t('View and download PDF/CSV')) ?></small></div><?= icon('chevron-right') ?></a>
        <a class="quick-link" href="<?= e(url('complaint/index')) ?>"><span class="ql-icon"><?= icon('alert') ?></span><div><strong><?= e(t('Complaints')) ?></strong><small><?= e(t(':n open ticket(s)', ['n' => $openTickets])) ?></small></div><?= icon('chevron-right') ?></a>
    </section>
</div>

<section class="section-block">
    <div class="block-head"><h2><?= e(t('Recommended for you')) ?></h2><a href="<?= e(url('equipment/index')) ?>"><?= e(t('View All')) ?> <?= icon('arrow-right', 'icon-xs') ?></a></div>
    <div class="equipment-grid">
        <?php $wish = (new Wishlist())->ids((int)$user['id']); foreach ($recommended as $e) { $free = null; $heart = true; $saved = in_array((int)$e['id'], $wish, true); include __DIR__ . '/../partials/eq_card.php'; } ?>
    </div>
</section>
