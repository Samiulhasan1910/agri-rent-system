<div class="wish-list">
    <?php foreach ($rows as $e): $quote = Pricing::tryQuote($e['price_per_day']); $avail = Availability::blockReason($e) === null && ($free[(int)$e['id']] ?? 0) > 0; ?>
        <article class="wish-item" data-row="wish-<?= (int)$e['id'] ?>">
            <img src="<?= e(equipment_image($e)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($e['category_icon']) ?>.svg'">
            <div class="wi-main">
                <h3><?= e($e['name']) ?></h3>
                <small><?= icon('map-pin', 'icon-xs') ?><?= e($e['location']) ?></small>
                <small><?= icon('calendar', 'icon-xs') ?><?= e(t('Daily Rental')) ?> · <?= e(t('Saved')) ?> <?= e(time_ago($e['saved_at'])) ?></small>
            </div>
            <div class="wi-price"><strong class="text-green"><?= $quote ? money($quote['price']) : '—' ?></strong><span>/<?= e(t('day')) ?></span><small><?= icon('star', 'icon-xs star-fill') ?><?= number_format((float)$e['rating'], 1) ?> (<?= (int)$e['review_count'] ?> <?= e(t('reviews')) ?>)</small></div>
            <div><?= $avail ? badge('available') : badge('unavailable') ?></div>
            <div class="wi-actions">
                <a class="btn btn-outline btn-sm" href="<?= e(url('equipment/show', ['id' => $e['id']])) ?>"><?= e(t('View Details')) ?></a>
                <form method="post" action="<?= e(url('wishlist/remove')) ?>" data-ajax data-remove-row="wish-<?= (int)$e['id'] ?>" data-confirm="<?= e(t('Remove :name from your wishlist?', ['name' => $e['name']])) ?>">
                    <?= csrf_field() ?><input type="hidden" name="equipment_id" value="<?= (int)$e['id'] ?>">
                    <button class="btn btn-danger-soft btn-sm" type="submit"><?= icon('trash', 'icon-xs') ?><?= e(t('Remove')) ?></button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <?php $icon = 'heart'; $heading = t('Your wishlist is empty'); $text = t('Tap the heart on any equipment to save it here.'); $action = '<a class="btn btn-primary btn-sm" href="' . e(url('equipment/index')) . '">' . e(t('Browse Equipment')) . '</a>'; include __DIR__ . '/../partials/empty.php'; unset($action); ?>
    <?php endif; ?>
</div>
