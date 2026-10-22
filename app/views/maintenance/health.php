<div class="page-head">
    <div><h1><?= e(t('Equipment Health Monitoring')) ?></h1><p class="muted"><?= e(t('Usage-based alerts prevent breakdowns. Equipment that needs maintenance cannot be booked.')) ?></p></div>
    <a class="btn btn-primary" href="<?= e(url('maintenance/index')) ?>"><?= icon('calendar') ?><?= e(t('Schedule Maintenance')) ?></a>
</div>
<section class="stats">
    <a class="stat" href="<?= e(url('maintenance/health', ['health' => 'good'])) ?>"><span class="stat-icon"><?= icon('check-circle') ?></span><div><strong><?= (int)$counts['good'] ?></strong><span><?= e(t('Good')) ?></span></div></a>
    <a class="stat" href="<?= e(url('maintenance/health', ['health' => 'needs_maintenance'])) ?>"><span class="stat-icon red"><?= icon('alert') ?></span><div><strong><?= (int)$counts['needs_maintenance'] ?></strong><span><?= e(t('Needs Maintenance')) ?></span></div></a>
    <a class="stat" href="<?= e(url('maintenance/health', ['health' => 'under_maintenance'])) ?>"><span class="stat-icon amber"><?= icon('wrench') ?></span><div><strong><?= (int)$counts['under_maintenance'] ?></strong><span><?= e(t('Under Maintenance')) ?></span></div></a>
    <a class="stat" href="<?= e(url('maintenance/health', ['health' => 'unknown'])) ?>"><span class="stat-icon gray"><?= icon('info') ?></span><div><strong><?= (int)$counts['unknown'] ?></strong><span><?= e(t('Data Missing')) ?></span></div></a>
</section>
<div class="grid-2-1">
    <section class="card">
        <form class="toolbar" method="get" action="index.php" data-live data-target="#healthTable">
            <input type="hidden" name="r" value="maintenance/health">
            <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search equipment')) ?>"></div>
            <select name="health"><option value=""><?= e(t('All health states')) ?></option><?php foreach (['good', 'needs_maintenance', 'under_maintenance', 'unknown'] as $h): ?><option value="<?= $h ?>"<?= selected($f['health'], $h) ?>><?= e(status_meta($h)[0]) ?></option><?php endforeach; ?></select>
        </form>
        <div id="healthTable"><?php include __DIR__ . '/_health_table.php'; ?></div>
    </section>
    <aside>
        <section class="card">
            <div class="card-head"><h2><?= icon('alert', 'icon-xs') ?> <?= e(t('Maintenance Alerts')) ?></h2></div>
            <div class="list">
                <?php foreach ($alerts as $a): ?>
                    <div class="list-item" data-row="alert-<?= (int)$a['id'] ?>">
                        <span class="li-icon <?= $a['type'] === 'missing_data' ? 'gray' : ($a['type'] === 'damaged' ? 'red' : 'amber') ?>"><?= icon($a['type'] === 'missing_data' ? 'info' : 'wrench') ?></span>
                        <div><strong><?= e($a['equipment_name']) ?></strong><small><?= e($a['message']) ?></small><small class="muted"><?= e(time_ago($a['created_at'])) ?></small></div>
                        <form method="post" action="<?= e(url('maintenance/resolve-alert')) ?>" data-ajax data-remove-row="alert-<?= (int)$a['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-light btn-xs" title="<?= e(t('Mark resolved')) ?>"><?= icon('check', 'icon-xs') ?></button></form>
                    </div>
                <?php endforeach; ?>
                <?php if (!$alerts): ?><p class="muted center"><?= e(t('No open alerts.')) ?></p><?php endif; ?>
            </div>
        </section>
        <section class="card">
            <h2 class="card-title"><?= e(t('Log equipment usage')) ?></h2>
            <form method="post" action="<?= e(url('maintenance/log-usage')) ?>" data-ajax class="stack" novalidate>
                <?= csrf_field() ?>
                <select name="equipment_id" required><option value=""><?= e(t('Select equipment')) ?></option><?php foreach ($options as $o): ?><option value="<?= (int)$o['id'] ?>"><?= e($o['name']) ?></option><?php endforeach; ?></select>
                <?= field_error('equipment_id') ?>
                <input type="number" step="0.5" min="0" name="hours" placeholder="<?= e(t('Hours used (leave empty if unknown)')) ?>"><?= field_error('hours') ?>
                <input name="note" placeholder="<?= e(t('Note')) ?>">
                <button class="btn btn-primary btn-sm" type="submit"><?= e(t('Save usage log')) ?></button>
            </form>
        </section>
    </aside>
</div>
<section class="card">
    <h2 class="card-title"><?= e(t('Recent usage logs')) ?></h2>
    <div class="table-wrap"><table class="table compact">
        <thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Booking')) ?></th><th><?= e(t('Hours')) ?></th><th><?= e(t('Source')) ?></th><th><?= e(t('Logged by')) ?></th></tr></thead>
        <tbody><?php foreach ($logs as $l): ?><tr class="<?= $l['flagged'] ? 'row-warn' : '' ?>"><td><?= e(fmt_datetime($l['created_at'])) ?></td><td><?= e($l['equipment_name']) ?></td><td><?= e($l['booking_code'] ?? '—') ?></td><td><?= $l['hours'] === null ? '<span class="badge badge-amber">' . e(t('Missing')) . '</span>' : e((string)$l['hours']) ?></td><td><?= e(t(ucfirst($l['source']))) ?></td><td><?= e($l['logged_by_name'] ?? '—') ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
</section>
