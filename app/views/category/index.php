<div class="page-head">
    <div><h1><?= e(t('Category Management')) ?></h1><p class="muted"><?= e(t('Organize equipment into clear categories')) ?></p></div>
    <div class="row-actions">
        <form method="get" action="index.php" data-live data-target="#catTable" class="input-icon"><input type="hidden" name="r" value="category/index"><?= icon('search') ?><input name="q" placeholder="<?= e(t('Search category...')) ?>"></form>
        <button class="btn btn-primary" type="button" data-modal-open="#categoryModal" data-category-new><?= icon('plus') ?><?= e(t('Add category')) ?></button>
    </div>
</div>

<div class="category-cards">
    <?php foreach ($rows as $c): ?>
        <article class="cat-card <?= $c['status'] === 'inactive' ? 'is-inactive' : '' ?>">
            <img src="assets/img/equipment/<?= e($c['icon']) ?>.svg" alt="">
            <strong><?= e(t($c['name'])) ?></strong>
            <small><?= (int)$c['equipment_count'] ?> <?= e(t('equipment')) ?></small>
        </article>
    <?php endforeach; ?>
</div>

<section class="card">
    <div id="catTable"><?php include __DIR__ . '/_table.php'; ?></div>
</section>

<div class="modal" id="categoryModal" role="dialog" aria-modal="true" hidden>
    <form class="modal-card" method="post" action="<?= e(url('category/store')) ?>" data-ajax data-category-form novalidate>
        <div class="modal-head"><h3 data-modal-title><?= e(t('Add category')) ?></h3><button type="button" class="btn-icon" data-modal-close aria-label="<?= e(t('Close')) ?>">×</button></div>
        <div class="modal-body">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="field"><label><?= e(t('Category name')) ?></label><input name="name" required maxlength="80"><?= field_error('name') ?></div>
            <div class="field"><label><?= e(t('Description')) ?></label><input name="description" maxlength="255"></div>
            <div class="form-grid">
                <div class="field"><label><?= e(t('Icon')) ?></label><select name="icon"><?php foreach (Category::ICONS as $i): ?><option value="<?= $i ?>"><?= e(ucfirst($i)) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label><?= e(t('Status')) ?></label><select name="status"><option value="active"><?= e(t('Active')) ?></option><option value="inactive"><?= e(t('Inactive')) ?></option></select></div>
            </div>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-primary" type="submit"><?= e(t('Save')) ?></button></div>
    </form>
</div>
