<div class="smart-list">
    <?php foreach ($results as $i => $e): $quote = Pricing::tryQuote($e['price_per_day']); ?>
        <article class="smart-item <?= $i === 0 ? 'best' : '' ?>">
            <span class="rank">#<?= $i + 1 ?></span>
            <img src="<?= e(equipment_image($e)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($e['category_icon']) ?>.svg'">
            <div class="si-main">
                <strong><?= e($e['name']) ?></strong>
                <small><?= e(t($e['category_name'])) ?> · <?= icon('map-pin', 'icon-xs') ?><?= e($e['location']) ?> · <?= icon('star', 'icon-xs star-fill') ?><?= number_format((float)$e['rating'], 1) ?></small>
                <small class="muted"><?= e($e['match_reason'] ?? '') ?><?= $e['size_fit'] ? '' : ' · ' . e(t('less suited to your farm size')) ?></small>
            </div>
            <div class="si-side">
                <strong class="text-green"><?= $quote ? money($quote['price']) : '—' ?><small>/<?= e(t('day')) ?></small></strong>
                <?= $e['available_now'] ? badge('available') : badge('unavailable') ?>
                <a class="btn btn-primary btn-xs" href="<?= e(url('equipment/show', ['id' => $e['id']])) ?>#book"><?= e(t('Select')) ?></a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
