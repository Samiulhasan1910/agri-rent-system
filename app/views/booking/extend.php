<nav class="crumbs"><a href="<?= e(url('booking/index')) ?>"><?= e(t('My Bookings')) ?></a><?= icon('chevron-right', 'icon-xs') ?><a href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= e(t('Booking Details')) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e(t('Rental Extension Request')) ?></span></nav>
<div class="page-head"><div><h1><?= e(t('Rental Extension Request')) ?></h1></div></div>
<section class="card">
    <div class="booking-head">
        <img src="<?= e(equipment_image($b)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($b['category_icon']) ?>.svg'">
        <div class="bh-main"><h2><?= e($b['equipment_name']) ?></h2></div>
        <dl class="kv kv-inline">
            <div><dt><?= e(t('Booking ID')) ?></dt><dd>#<?= e($b['booking_code']) ?></dd></div>
            <div><dt><?= e(t('Current Return Date')) ?></dt><dd><?= e(fmt_date($b['end_date'])) ?></dd></div>
            <?php if ($b['owner_name']): ?><div><dt><?= e(t('Owner')) ?></dt><dd><?= e($b['owner_name']) ?></dd></div><?php endif; ?>
        </dl>
    </div>
    <form method="post" action="<?= e(url('booking/extend')) ?>" data-ajax data-extension="<?= (int)$b['id'] ?>" novalidate>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
        <div class="form-grid">
            <div class="field"><label><?= e(t('Current Return Date')) ?></label><input value="<?= e(fmt_date($b['end_date'])) ?>" disabled></div>
            <div class="field"><label for="ne"><?= e(t('New Return Date')) ?> *</label><input id="ne" type="date" name="new_end_date" min="<?= e(date('Y-m-d', strtotime($b['end_date'] . ' +1 day'))) ?>" required><?= field_error('new_end_date') ?></div>
            <div class="field"><label><?= e(t('Extra Days')) ?></label><input data-x="extra_days" value="—" disabled></div>
            <div class="field"><label><?= e(t('Extra Cost')) ?></label><input data-x="extra_cost" value="৳ —" disabled></div>
            <div class="field span-2"><label><?= e(t('Updated rental')) ?></label><p class="quote-msg" data-x="message"><?= e(t('Select a new return date to check availability and cost.')) ?></p></div>
            <div class="field span-2"><label for="er"><?= e(t('Reason for Extension')) ?> *</label><textarea id="er" name="reason" rows="3" maxlength="500" placeholder="<?= e(t('Please explain why you need more time...')) ?>" data-counter required></textarea><small class="hint right" data-counter-label>0/500</small><?= field_error('reason') ?></div>
        </div>
        <div class="alert alert-info"><?= icon('info') ?><span><?= e(t('Note: Your extension request is subject to approval. Extra days are charged at your original daily rate; you will be notified once the request is reviewed.')) ?></span></div>
        <div class="form-actions split">
            <a class="btn btn-outline" href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= e(t('Cancel')) ?></a>
            <button class="btn btn-primary" type="submit" data-extend-btn><?= icon('send') ?><?= e(t('Submit Extension Request')) ?></button>
        </div>
    </form>
</section>
