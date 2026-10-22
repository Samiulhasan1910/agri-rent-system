<div class="page-head">
    <div><h1><?= e(t('Payment History')) ?></h1><p class="muted"><?= e(t('View the details of all your payments and invoices.')) ?></p></div>
    <a class="btn btn-outline" href="<?= e(url('payment/export', array_filter($f))) ?>"><?= icon('download') ?><?= e(t('Export')) ?></a>
</div>
<section class="stats">
    <article class="stat"><span class="stat-icon"><?= icon('receipt') ?></span><div><strong><?= (int)$stats['total_count'] ?></strong><span><?= e(t('Total Payments')) ?></span><small class="trend"><?= e(t('All time transactions')) ?></small></div></article>
    <article class="stat"><span class="stat-icon blue"><?= icon('banknote') ?></span><div><strong><?= money($stats['paid_total']) ?></strong><span><?= e(t('Total Amount Paid')) ?></span><small class="trend"><?= e(t('All time')) ?></small></div></article>
    <article class="stat"><span class="stat-icon"><?= icon('check-circle') ?></span><div><strong><?= (int)$stats['paid_count'] ?></strong><span><?= e(t('Successful Payments')) ?></span><small class="trend"><?= e(t('Completed')) ?></small></div></article>
    <article class="stat"><span class="stat-icon amber"><?= icon('clock') ?></span><div><strong><?= (int)$stats['pending_count'] ?></strong><span><?= e(t('Pending Payments')) ?></span><small class="trend"><?= e(t('Awaiting payment')) ?></small></div></article>
</section>
<?php if ($dues): ?>
    <section class="card due-card">
        <div class="card-head"><h2><?= icon('alert', 'icon-xs') ?> <?= e(t('Balance due')) ?></h2></div>
        <?php foreach ($dues as $d): ?>
            <div class="pay-row"><span><strong>#<?= e($d['booking_code']) ?></strong> · <?= e($d['equipment_name']) ?></span><strong class="text-danger"><?= money(Booking::balance($d)) ?></strong>
                <a class="btn btn-primary btn-xs" href="<?= e(url('payment/checkout', ['booking' => $d['id']])) ?>"><?= e(t('Pay now')) ?></a></div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#payList">
        <input type="hidden" name="r" value="payment/history">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search by Booking ID or Transaction ID')) ?>"></div>
        <label class="inline-field"><span><?= e(t('From')) ?></span><input type="date" name="from" value="<?= e($f['from']) ?>"></label>
        <label class="inline-field"><span><?= e(t('To')) ?></span><input type="date" name="to" value="<?= e($f['to']) ?>"></label>
        <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (['paid', 'pending', 'failed', 'refunded'] as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
    </form>
    <div id="payList"><?php include __DIR__ . '/_history.php'; ?></div>
</section>
<a class="back-link" href="<?= e(url('dashboard/index')) ?>"><?= icon('arrow-left', 'icon-xs') ?><?= e(t('Back to dashboard')) ?></a>
