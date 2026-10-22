<div class="page-head">
    <div><h1><?= e(t('Rental History')) ?></h1><p class="muted"><?= e(t('Track your past rentals and their final status.')) ?></p></div>
    <div class="row-actions">
        <a class="btn btn-outline" href="<?= e(url('booking/download', array_filter($f) + ['format' => 'csv'])) ?>" data-download><?= icon('download') ?>CSV</a>
        <a class="btn btn-primary" href="<?= e(url('booking/download', array_filter($f) + ['format' => 'pdf'])) ?>" data-download><?= icon('download') ?><?= e(t('Download PDF')) ?></a>
    </div>
</div>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#historyList" data-sync-downloads>
        <input type="hidden" name="r" value="booking/history">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search by equipment name or booking ID...')) ?>"></div>
        <label class="inline-field"><span><?= e(t('From')) ?></span><input type="date" name="from" value="<?= e($f['from']) ?>"></label>
        <label class="inline-field"><span><?= e(t('To')) ?></span><input type="date" name="to" value="<?= e($f['to']) ?>"></label>
        <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (['completed', 'delivered', 'returned', 'confirmed', 'pending', 'cancelled', 'rejected'] as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
    </form>
    <div id="historyList"><?php include __DIR__ . '/_history.php'; ?></div>
</section>
