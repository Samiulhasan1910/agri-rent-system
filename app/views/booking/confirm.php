<nav class="crumbs"><a href="<?= e(url('equipment/index')) ?>"><?= e(t('Browse Equipment')) ?></a><?= icon('chevron-right', 'icon-xs') ?><a href="<?= e(url('equipment/show', ['id' => $equipment['id']])) ?>"><?= e($equipment['name']) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e(t('Confirm Your Booking')) ?></span></nav>
<div class="page-head"><div><h1><?= e(t('Confirm Your Booking')) ?></h1><p class="muted"><?= e(t('Please check your rental details before payment.')) ?></p></div></div>

<form class="card" method="post" action="<?= e(url('booking/store')) ?>" data-ajax data-uuid novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="equipment_id" value="<?= (int)$equipment['id'] ?>">
    <input type="hidden" name="start_date" value="<?= e($start) ?>">
    <input type="hidden" name="end_date" value="<?= e($end) ?>">
    <input type="hidden" name="client_uuid" value="">

    <div class="confirm-eq">
        <img src="<?= e(equipment_image($equipment)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($equipment['category_icon']) ?>.svg'">
        <div><h2><?= e($equipment['name']) ?></h2><p class="muted"><?= e($equipment['specs'] ?? $equipment['category_name']) ?></p><strong class="price-lg"><?= money($quote['price_per_day']) ?> <small>/ <?= e(t('day')) ?></small></strong></div>
        <span class="badge badge-green badge-lg"><?= icon('check', 'icon-xs') ?><?= e(t('Ready to Book')) ?></span>
    </div>

    <div class="confirm-grid">
        <section class="sub-card">
            <h3><?= icon('calendar') ?><?= e(t('Booking Information')) ?></h3>
            <dl class="kv">
                <div><dt><?= e(t('Start Date')) ?></dt><dd><?= e(fmt_date($start)) ?></dd></div>
                <div><dt><?= e(t('End Date')) ?></dt><dd><?= e(fmt_date($end)) ?></dd></div>
                <div><dt><?= e(t('Total Days')) ?></dt><dd><?= (int)$quote['days'] ?> <?= e(t('days')) ?></dd></div>
                <div><dt><?= e(t('Season')) ?></dt><dd><span class="season-tag season-<?= e($quote['season']) ?>"><?= e($quote['season_label']) ?></span></dd></div>
                <div><dt><?= e(t('Units available')) ?></dt><dd><?= (int)$quote['available_units'] ?></dd></div>
            </dl>
        </section>
        <section class="sub-card">
            <h3><?= icon('receipt') ?><?= e(t('Cost Details')) ?></h3>
            <dl class="kv">
                <div><dt><?= e(t('Rental Cost')) ?> <small>(<?= (int)$quote['days'] ?> × <?= money($quote['price_per_day']) ?>)</small></dt><dd><?= money($quote['rental_cost']) ?></dd></div>
                <?php if ($quote['season'] !== 'normal'): ?><div><dt><?= e(t('Base price')) ?></dt><dd><?= money($quote['base_price']) ?> / <?= e(t('day')) ?></dd></div><?php endif; ?>
                <div><dt><?= e(t('Delivery Cost')) ?></dt><dd><?= money($quote['delivery_charge']) ?></dd></div>
                <div class="kv-total"><dt><?= e(t('Total Cost')) ?></dt><dd><?= money($quote['total']) ?></dd></div>
                <div><dt><?= e(t('Payment Status')) ?></dt><dd><?= badge('pending') ?></dd></div>
            </dl>
        </section>
    </div>

    <div class="form-grid">
        <div class="field">
            <label for="addr"><?= icon('map-pin', 'icon-xs') ?> <?= e(t('Delivery Address')) ?></label>
            <textarea id="addr" name="delivery_address" rows="2" maxlength="255" required><?= e(old('delivery_address', $user['address'])) ?></textarea>
            <?= field_error('delivery_address') ?>
        </div>
        <div class="field">
            <label for="notes"><?= e(t('Notes for the delivery team (optional)')) ?></label>
            <textarea id="notes" name="notes" rows="2" maxlength="500" placeholder="<?= e(t('e.g. call before arriving')) ?>"><?= e(old('notes')) ?></textarea>
        </div>
    </div>

    <div class="form-actions split">
        <a class="btn btn-outline" href="<?= e(url('equipment/show', ['id' => $equipment['id'], 'start_date' => $start, 'end_date' => $end])) ?>"><?= icon('x') ?><?= e(t('Cancel Booking')) ?></a>
        <button class="btn btn-primary btn-lg" type="submit"><?= e(t('Confirm Booking')) ?><?= icon('arrow-right') ?></button>
    </div>
</form>
<a class="back-link" href="<?= e(url('equipment/show', ['id' => $equipment['id']])) ?>"><?= icon('arrow-left', 'icon-xs') ?><?= e(t('Back to Equipment Details')) ?></a>
