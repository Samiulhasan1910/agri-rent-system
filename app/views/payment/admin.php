<div class="page-head">
    <div><h1><?= e(t('Payment And Invoice Management')) ?></h1><p class="muted"><?= e(t('Track transactions, generate invoices and manage payment records')) ?></p></div>
    <a class="btn btn-outline" href="<?= e(url('payment/export', array_filter($f))) ?>" data-sync-href><?= icon('download') ?><?= e(t('Export CSV')) ?></a>
</div>
<section class="stats">
    <article class="stat"><span class="stat-icon"><?= icon('banknote') ?></span><div><strong><?= money($stats['paid_total']) ?></strong><span><?= e(t('Total Paid')) ?></span><small class="trend"><?= (int)$stats['paid_count'] ?> <?= e(t('payments')) ?></small></div></article>
    <article class="stat"><span class="stat-icon amber"><?= icon('clock') ?></span><div><strong><?= money($stats['pending_total']) ?></strong><span><?= e(t('Pending Payment')) ?></span><small class="trend"><?= (int)$stats['pending_count'] ?> <?= e(t('cash on delivery')) ?></small></div></article>
    <article class="stat"><span class="stat-icon red"><?= icon('x-circle') ?></span><div><strong><?= (int)$stats['failed_count'] ?></strong><span><?= e(t('Failed Payment')) ?></span><small class="trend"><?= e(t('Needs follow-up')) ?></small></div></article>
    <article class="stat"><span class="stat-icon blue"><?= icon('file-text') ?></span><div><strong><?= (int)$stats['invoice_count'] ?></strong><span><?= e(t('Invoices Generated')) ?></span><small class="trend"><?= e(t('All time')) ?></small></div></article>
</section>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#payAdmin">
        <input type="hidden" name="r" value="payment/admin">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search invoice, booking, transaction or farmer')) ?>"></div>
        <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (['paid', 'pending', 'failed', 'refunded', 'cancelled'] as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
        <select name="method"><option value=""><?= e(t('All Methods')) ?></option><?php foreach (Payment::METHODS as $k => $l): ?><option value="<?= $k ?>"<?= selected($f['method'], $k) ?>><?= e(t($l)) ?></option><?php endforeach; ?></select>
        <label class="inline-field"><span><?= e(t('From')) ?></span><input type="date" name="from" value="<?= e($f['from']) ?>"></label>
        <label class="inline-field"><span><?= e(t('To')) ?></span><input type="date" name="to" value="<?= e($f['to']) ?>"></label>
    </form>
    <div id="payAdmin"><?php include __DIR__ . '/_admin_table.php'; ?></div>
</section>
