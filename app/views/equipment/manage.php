<div class="page-head">
    <div><h1><?= e(t('Manage Equipment')) ?></h1><p class="muted"><?= e(t('Add, update, remove and monitor agricultural equipment')) ?></p></div>
    <a class="btn btn-primary" href="<?= e(url('equipment/create')) ?>"><?= icon('plus') ?><?= e(t('Add Equipment')) ?></a>
</div>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#eqTable">
        <input type="hidden" name="r" value="equipment/manage">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(t('Search equipment by name...')) ?>"></div>
        <select name="category"><option value=""><?= e(t('All Categories')) ?></option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($filters['category'], $c['id']) ?>><?= e(t($c['name'])) ?></option><?php endforeach; ?></select>
        <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (['available', 'unavailable', 'maintenance'] as $s): ?><option value="<?= $s ?>"<?= selected($filters['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
        <select name="location"><option value=""><?= e(t('All Location')) ?></option><?php foreach ($locations as $l): ?><option<?= selected($filters['location'], $l) ?>><?= e($l) ?></option><?php endforeach; ?></select>
    </form>
    <div id="eqTable"><?php include __DIR__ . '/_table.php'; ?></div>
</section>
