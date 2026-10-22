<div class="page-head"><div><h1><?= e(t('My Bookings')) ?></h1><p class="muted"><?= e(t('Here are your current equipment bookings.')) ?></p></div>
    <a class="btn btn-outline" href="<?= e(url('booking/history')) ?>"><?= icon('history') ?><?= e(t('Rental History')) ?></a></div>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#bookingList">
        <input type="hidden" name="r" value="booking/index">
        <input type="hidden" name="tab" value="<?= e($tab) ?>" data-tab-input>
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Search by equipment name or booking ID...')) ?>"></div>
        <div class="chips" role="tablist">
            <?php foreach (['all' => 'All', 'active' => 'Active', 'pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $label): ?>
                <button type="button" class="chip chip-<?= $k ?> <?= $tab === $k ? 'active' : '' ?>" data-tab="<?= $k ?>"><?= e(t($label)) ?></button>
            <?php endforeach; ?>
        </div>
    </form>
    <div id="bookingList"><?php include __DIR__ . '/_list.php'; ?></div>
</section>
