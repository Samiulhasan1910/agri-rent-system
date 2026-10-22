<div class="page-head"><div><h1><?= e(t('Wishlist')) ?></h1><p class="muted"><?= e(t('Your saved equipment for future rentals.')) ?></p></div></div>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#wishList">
        <input type="hidden" name="r" value="wishlist/index">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Search equipment')) ?>"></div>
        <select name="availability"><option value=""><?= e(t('All Status')) ?></option><option value="available"<?= selected($availability, 'available') ?>><?= e(t('Available')) ?></option><option value="unavailable"<?= selected($availability, 'unavailable') ?>><?= e(t('Not Available')) ?></option></select>
    </form>
    <div id="wishList"><?php include __DIR__ . '/_list.php'; ?></div>
</section>
