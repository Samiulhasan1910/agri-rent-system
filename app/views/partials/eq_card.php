<?php
/** Equipment card. Expects $e; optional $free (units free today), $saved (bool), $heart (bool). */
$quote = Pricing::tryQuote($e['price_per_day']);
$blocked = Availability::blockReason($e);
$isAvailable = $blocked === null && (!isset($free) || $free === null || $free > 0);
$fallback = 'assets/img/equipment/' . e($e['category_icon'] ?? 'tractor') . '.svg';
?>
<article class="eq-card" data-equipment="<?= (int)$e['id'] ?>">
    <a class="eq-media" href="<?= e(url('equipment/show', ['id' => $e['id']])) ?>">
        <img src="<?= e(equipment_image($e)) ?>" alt="<?= e($e['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= $fallback ?>'">
        <span class="eq-status"><?= $isAvailable ? badge('available') : badge('unavailable', $blocked && $e['health_status'] !== 'good' && $e['health_status'] !== 'unknown' ? t('Maintenance') : null) ?></span>
    </a>
    <?php if (!empty($heart)): ?>
        <button class="heart <?= !empty($saved) ? 'on' : '' ?>" type="button" data-wishlist="<?= (int)$e['id'] ?>" data-saved="<?= !empty($saved) ? '1' : '0' ?>" aria-label="<?= e(t('Save to wishlist')) ?>"><?= icon('heart') ?></button>
    <?php endif; ?>
    <div class="eq-body">
        <div class="eq-title">
            <h3><a href="<?= e(url('equipment/show', ['id' => $e['id']])) ?>"><?= e($e['name']) ?></a></h3>
            <a class="btn btn-outline btn-xs" href="<?= e(url('equipment/show', ['id' => $e['id']])) ?>"><?= e(t('Details')) ?></a>
        </div>
        <?php if (!empty($e['specs'])): ?><p class="eq-specs"><?= e($e['specs']) ?></p><?php endif; ?>
        <div class="eq-price">
            <?php if ($quote): ?>
                <strong><?= money($quote['price']) ?></strong><span>/<?= e(t('day')) ?></span>
                <?php if ($quote['season'] !== 'normal'): ?><small class="season-tag season-<?= e($quote['season']) ?>" title="<?= e(t('Base price')) ?> <?= money($quote['base']) ?>"><?= e(Pricing::label($quote['season'])) ?></small><?php endif; ?>
            <?php else: ?>
                <span class="muted"><?= e(t('Price unavailable')) ?></span>
            <?php endif; ?>
        </div>
        <div class="eq-meta">
            <span><?= icon('map-pin', 'icon-xs') ?><?= e($e['location']) ?></span>
            <span class="rating"><?= icon('star', 'icon-xs star-fill') ?><?= number_format((float)$e['rating'], 1) ?> <small>(<?= (int)$e['review_count'] ?> <?= e(t('reviews')) ?>)</small></span>
        </div>
    </div>
</article>
