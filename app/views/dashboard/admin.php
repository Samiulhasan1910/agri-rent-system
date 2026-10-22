<div class="page-head">
    <div><h1><?= e(t('Admin Dashboard')) ?></h1><p class="muted"><?= e(t('Monitor rentals, equipment, payment and system activity')) ?></p></div>
    <div class="row-actions">
        <a class="btn btn-outline" href="<?= e(url('analytics/index')) ?>"><?= icon('chart') ?><?= e(t('Reports')) ?></a>
        <a class="btn btn-primary" href="<?= e(url('equipment/create')) ?>"><?= icon('plus') ?><?= e(t('Add Equipment')) ?></a>
    </div>
</div>

<section class="stats">
    <article class="stat"><span class="stat-icon"><?= icon('tractor') ?></span><div><strong><?= (int)$stats['equipment'] ?></strong><span><?= e(t('Total Equipment')) ?></span><small class="trend"><?= e(t(':n need maintenance', ['n' => $health['needs_maintenance'] + $health['under_maintenance']])) ?></small></div></article>
    <article class="stat"><span class="stat-icon blue"><?= icon('calendar-check') ?></span><div><strong><?= (int)$stats['active'] ?></strong><span><?= e(t('Active Rentals')) ?></span><small class="trend"><?= e(t('Ongoing rentals')) ?></small></div></article>
    <article class="stat"><span class="stat-icon amber"><?= icon('banknote') ?></span><div><strong><?= money($stats['revenue']) ?></strong><span><?= e(t('Revenue')) ?></span><small class="trend"><?= e(t('This month')) ?></small></div></article>
    <article class="stat"><span class="stat-icon red"><?= icon('clock') ?></span><div><strong><?= (int)$stats['pending'] ?></strong><span><?= e(t('Pending Request')) ?></span><small class="trend"><?= e(t('Bookings & verifications')) ?></small></div></article>
</section>

<div class="grid-2-1">
    <section class="card">
        <div class="card-head"><div><h2><?= e(t('Rental Overview')) ?></h2><small class="muted"><?= e(t('Monthly booking requests')) ?></small></div><a class="btn btn-outline btn-sm" href="<?= e(url('analytics/index')) ?>"><?= e(t('Details')) ?></a></div>
        <div class="chart" data-chart="bar" data-labels='<?= e(json_encode($chart['labels'])) ?>' data-values='<?= e(json_encode($chart['values'])) ?>' data-series-label="<?= e(t('Bookings')) ?>"></div>
    </section>
    <section class="card">
        <div class="card-head"><h2><?= e(t('Recent Alerts')) ?></h2><a class="btn-link" href="<?= e(url('notification/index')) ?>"><?= e(t('View All')) ?></a></div>
        <div class="list">
            <?php foreach ($alerts as $a): $iconMap = ['danger' => ['x-circle', 'red'], 'warning' => ['alert', 'amber'], 'success' => ['check-circle', 'green'], 'info' => ['info', 'blue']]; [$ai, $ac] = $iconMap[$a['type']] ?? ['info', 'blue']; ?>
                <a class="list-item" href="<?= e($a['link'] ?: url('notification/index')) ?>">
                    <span class="li-icon <?= $ac ?>"><?= icon($ai) ?></span>
                    <div><strong><?= e(t($a['title'])) ?></strong><small><?= e(str_limit($a['message'], 70)) ?></small></div>
                    <small class="muted nowrap"><?= e(time_ago($a['created_at'])) ?></small>
                </a>
            <?php endforeach; ?>
            <?php if (!$alerts): ?><p class="muted center"><?= e(t('No alerts.')) ?></p><?php endif; ?>
        </div>
        <div class="mini-stats">
            <a href="<?= e(url('rental/index')) ?>"><strong><?= (int)$pendingExtensions ?></strong><span><?= e(t('Extensions')) ?></span></a>
            <a href="<?= e(url('emergency/index')) ?>"><strong><?= (int)$pendingEmergencies ?></strong><span><?= e(t('Emergencies')) ?></span></a>
            <a href="<?= e(url('complaint/index')) ?>"><strong><?= (int)$openComplaints ?></strong><span><?= e(t('Open tickets')) ?></span></a>
        </div>
    </section>
</div>

<section class="card">
    <div class="card-head"><h2><?= e(t('Recent rentals')) ?></h2><a class="btn btn-outline btn-sm" href="<?= e(url('rental/index')) ?>"><?= e(t('View All')) ?></a></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Farmer')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Dates')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Payment')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $b): ?>
            <tr><td><strong><?= e($b['booking_code']) ?></strong></td><td><?= e($b['farmer_name']) ?></td><td><?= e($b['equipment_name']) ?></td>
                <td><?= e(fmt_date($b['start_date'], 'd M')) ?> – <?= e(fmt_date($b['end_date'], 'd M Y')) ?></td><td><?= badge($b['status']) ?></td><td><?= badge(Booking::paymentState($b)) ?></td>
                <td><a class="btn btn-light btn-xs" href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= e(t('View')) ?></a></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
