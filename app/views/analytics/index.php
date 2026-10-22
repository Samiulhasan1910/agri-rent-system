<div class="page-head">
    <div><h1><?= e(t('Reports / Analytics')) ?></h1><p class="muted"><?= e(t('Analyze revenue, equipment usage and system performance')) ?></p></div>
</div>

<section class="stats">
    <article class="stat"><span class="stat-icon"><?= icon('banknote') ?></span><div><strong><?= money($summary['revenue']) ?></strong><span><?= e(t('Monthly Revenue')) ?></span><small class="trend"><?= e(t('This month')) ?></small></div></article>
    <article class="stat"><span class="stat-icon blue"><?= icon('calendar-check') ?></span><div><strong><?= (int)$summary['bookings'] ?></strong><span><?= e(t('Total Bookings')) ?></span><small class="trend"><?= e(t('This month')) ?></small></div></article>
    <article class="stat"><span class="stat-icon amber"><?= icon('tractor') ?></span><div><strong class="ellipsis"><?= e($top['name'] ?? '—') ?></strong><span><?= e(t('Most Rented Equipment')) ?></span><small class="trend"><?= (int)($top['rentals'] ?? 0) ?> <?= e(t('bookings')) ?></small></div></article>
    <article class="stat"><span class="stat-icon purple"><?= icon('refresh') ?></span><div><strong><?= e((string)$summary['return_rate']) ?>%</strong><span><?= e(t('Return Rate')) ?></span><small class="trend"><?= e(t('Returned on time')) ?></small></div></article>
</section>

<section class="card">
    <form class="toolbar" id="analyticsFilter" data-analytics="<?= e(url('analytics/data')) ?>" novalidate>
        <label class="inline-field"><span><?= e(t('From')) ?></span><input type="date" name="from" value="<?= e($from) ?>" required></label>
        <label class="inline-field"><span><?= e(t('To')) ?></span><input type="date" name="to" value="<?= e($to) ?>" required></label>
        <select name="equipment"><option value=""><?= e(t('All equipment')) ?></option><?php foreach ($equipment as $eq): ?><option value="<?= (int)$eq['id'] ?>"><?= e($eq['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit"><?= icon('filter') ?><?= e(t('Apply')) ?></button>
        <label class="check"><input type="checkbox" data-auto-refresh checked> <span><?= e(t('Auto refresh (60s)')) ?></span></label>
        <small class="muted" data-refreshed></small>
    </form>
    <div class="alert alert-danger" data-analytics-error hidden></div>
    <div class="kpi-row" data-kpis>
        <div><small><?= e(t('Revenue (paid)')) ?></small><strong data-kpi="revenue_fmt">—</strong></div>
        <div><small><?= e(t('Bookings')) ?></small><strong data-kpi="bookings">—</strong></div>
        <div><small><?= e(t('Booking value')) ?></small><strong data-kpi="booking_value_fmt">—</strong></div>
        <div><small><?= e(t('Avg. booking')) ?></small><strong data-kpi="avg_fmt">—</strong></div>
        <div><small><?= e(t('Rental days')) ?></small><strong data-kpi="rental_days">—</strong></div>
        <div><small><?= e(t('Cancelled')) ?></small><strong data-kpi="cancelled">—</strong></div>
    </div>
    <div class="empty-inline" data-analytics-empty hidden><?= icon('chart') ?> <?= e(t('No data available')) ?></div>
</section>

<div class="grid-2-1">
    <section class="card"><div class="card-head"><h2><?= e(t('Revenue Trend')) ?></h2></div><div class="chart" id="revenueChart"></div></section>
    <section class="card"><div class="card-head"><h2><?= e(t('Equipment Usage')) ?></h2><small class="muted"><?= e(t('rental days')) ?></small></div><div class="chart" id="usageChart"></div></section>
</div>
<div class="grid-2-1">
    <section class="card"><div class="card-head"><h2><?= e(t('Bookings')) ?></h2></div><div class="chart" id="bookingChart"></div></section>
    <section class="card"><div class="card-head"><h2><?= e(t('Booking Status')) ?></h2></div><div class="chart" id="statusChart"></div></section>
</div>

<section class="card">
    <div class="card-head"><h2><?= e(t('Generated Reports')) ?></h2></div>
    <form class="toolbar" method="post" action="<?= e(url('analytics/generate')) ?>" data-ajax data-prepend-row="#reportRows" novalidate>
        <?= csrf_field() ?>
        <select name="type" required><option value=""><?= e(t('Report type')) ?></option><?php foreach (Report::TYPES as $k => $l): ?><option value="<?= $k ?>"><?= e(t($l)) ?></option><?php endforeach; ?></select>
        <label class="inline-field"><span><?= e(t('From')) ?></span><input type="date" name="from" value="<?= e(date('Y-m-01')) ?>" required></label>
        <label class="inline-field"><span><?= e(t('To')) ?></span><input type="date" name="to" value="<?= e(today()) ?>" required></label>
        <button class="btn btn-primary" type="submit"><?= icon('file-text') ?><?= e(t('Generate Report')) ?></button>
        <?= field_error('type') ?><?= field_error('to') ?>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th><?= e(t('Report Name')) ?></th><th><?= e(t('Type')) ?></th><th><?= e(t('Date Range')) ?></th><th><?= e(t('Rows')) ?></th><th class="right"><?= e(t('Total')) ?></th><th><?= e(t('Generated By')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody id="reportRows"><?php foreach ($reports as $r) { include __DIR__ . '/_report_row.php'; } ?></tbody>
    </table></div>
</section>
