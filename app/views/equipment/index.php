<section class="card">
    <div class="card-head"><h1 class="h2"><?= e(t('Browse Equipment')) ?></h1>
        <span class="season-pill season-<?= e($season) ?>"><?= icon('sprout', 'icon-xs') ?> <?= e(Pricing::label($season)) ?></span></div>
    <form class="filters" method="get" action="index.php" data-live data-target="#results" data-offline-catalog>
        <input type="hidden" name="r" value="equipment/index">
        <input type="hidden" name="category" value="<?= (int)$filters['category'] ?: '' ?>" data-category-input>
        <div class="search-bar">
            <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(t('Search tractor, pump, tiller...')) ?>" aria-label="<?= e(t('Search')) ?>"></div>
            <select name="location" aria-label="<?= e(t('Location')) ?>"><option value=""><?= e(t('All locations')) ?></option><?php foreach ($locations as $l): ?><option<?= selected($filters['location'], $l) ?>><?= e($l) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary" type="submit"><?= icon('search') ?><?= e(t('Search')) ?></button>
        </div>
        <h3 class="sub-title"><?= e(t('Browse by Category')) ?></h3>
        <div class="category-row compact" role="radiogroup" aria-label="<?= e(t('Category')) ?>">
            <button type="button" class="category-tile <?= !$filters['category'] ? 'active' : '' ?>" data-category="">
                <span class="all-icon"><?= icon('grid') ?></span><strong><?= e(t('All')) ?></strong>
            </button>
            <?php foreach ($categories as $c): ?>
                <button type="button" class="category-tile <?= (int)$filters['category'] === (int)$c['id'] ? 'active' : '' ?>" data-category="<?= (int)$c['id'] ?>">
                    <img src="assets/img/equipment/<?= e($c['icon']) ?>.svg" alt="" loading="lazy"><strong><?= e(t($c['name'])) ?></strong><small><?= (int)$c['total'] ?></small>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="filter-row">
            <label><span><?= e(t('Min price / day')) ?></span><input type="number" min="0" step="50" name="min_price" value="<?= e($filters['min_price']) ?>" placeholder="৳ 0"></label>
            <label><span><?= e(t('Max price / day')) ?></span><input type="number" min="0" step="50" name="max_price" value="<?= e($filters['max_price']) ?>" placeholder="<?= e(t('Any')) ?>"></label>
            <label><span><?= e(t('Availability')) ?></span><select name="availability"><option value=""><?= e(t('All')) ?></option><option value="available"<?= selected($filters['availability'], 'available') ?>><?= e(t('Available')) ?></option><option value="unavailable"<?= selected($filters['availability'], 'unavailable') ?>><?= e(t('Not Available')) ?></option></select></label>
            <label><span><?= e(t('Sort by')) ?></span><select name="sort">
                <?php foreach (['newest' => 'Newest', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low', 'rating' => 'Top rated', 'name' => 'Name: A to Z'] as $k => $label): ?><option value="<?= $k ?>"<?= selected($filters['sort'], $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?>
            </select></label>
            <a class="btn btn-light" href="<?= e(url('equipment/index')) ?>"><?= icon('refresh') ?><?= e(t('Reset')) ?></a>
        </div>
    </form>
</section>

<section class="section-block">
    <div class="block-head"><h2><?= e(t('All Equipment')) ?> <small class="muted" data-result-count>(<?= (int)$meta['total'] ?>)</small></h2></div>
    <div id="results" aria-live="polite"><?php include __DIR__ . '/_results.php'; ?></div>
</section>
