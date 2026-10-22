<div class="page-head">
    <div><h1><?= e(t('Schedule Equipment Maintenance')) ?></h1><p class="muted"><?= e(t('Plan maintenance so equipment remains usable and safe. Equipment cannot be booked during maintenance.')) ?></p></div>
    <a class="btn btn-outline" href="<?= e(url('maintenance/health')) ?>"><?= icon('activity') ?><?= e(t('Equipment Health')) ?></a>
</div>
<section class="stats">
    <article class="stat"><span class="stat-icon blue"><?= icon('calendar') ?></span><div><strong><?= (int)$counts['scheduled'] ?></strong><span><?= e(t('Scheduled')) ?></span></div></article>
    <article class="stat"><span class="stat-icon amber"><?= icon('wrench') ?></span><div><strong><?= (int)$counts['in_progress'] ?></strong><span><?= e(t('In Maintenance')) ?></span></div></article>
    <article class="stat"><span class="stat-icon"><?= icon('check-circle') ?></span><div><strong><?= (int)$counts['completed'] ?></strong><span><?= e(t('Completed')) ?></span></div></article>
    <article class="stat"><span class="stat-icon red"><?= icon('alert') ?></span><div><strong><?= count($alerts) ?></strong><span><?= e(t('Open alerts')) ?></span></div></article>
</section>
<div class="grid-1-2">
    <section class="card">
        <h2 class="card-title"><?= e(t('New maintenance')) ?></h2>
        <form method="post" action="<?= e(url('maintenance/store')) ?>" data-ajax data-reset novalidate>
            <?= csrf_field() ?>
            <div class="field"><label for="m-eq"><?= e(t('Equipment')) ?></label><select id="m-eq" name="equipment_id" required><option value=""><?= e(t('Select equipment')) ?></option><?php foreach ($equipment as $eq): ?><option value="<?= (int)$eq['id'] ?>"<?= selected($preselect, $eq['id']) ?>><?= e($eq['name']) ?><?= $eq['health_status'] === 'needs_maintenance' ? ' ⚠' : '' ?></option><?php endforeach; ?></select><?= field_error('equipment_id') ?></div>
            <div class="form-grid">
                <div class="field"><label for="m-start"><?= e(t('Maintenance Date')) ?></label><input id="m-start" type="date" name="scheduled_date" min="<?= today() ?>" required><?= field_error('scheduled_date') ?></div>
                <div class="field"><label for="m-end"><?= e(t('Expected completion')) ?></label><input id="m-end" type="date" name="end_date" min="<?= today() ?>"><?= field_error('end_date') ?></div>
            </div>
            <div class="form-grid">
                <div class="field"><label><?= e(t('Type')) ?></label><select name="type"><option value="routine"><?= e(t('Routine service')) ?></option><option value="repair"><?= e(t('Repair')) ?></option><option value="inspection"><?= e(t('Inspection')) ?></option></select></div>
                <div class="field"><label><?= e(t('Status')) ?></label><select name="status"><option value="scheduled"><?= e(t('Scheduled')) ?></option><option value="in_progress"><?= e(t('In Maintenance (start today)')) ?></option></select></div>
            </div>
            <div class="field"><label for="m-notes"><?= e(t('Work description')) ?></label><textarea id="m-notes" name="notes" rows="3" maxlength="500" required></textarea><?= field_error('notes') ?></div>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('calendar-check') ?><?= e(t('Save')) ?></button>
        </form>
    </section>
    <section class="card">
        <div class="card-head"><h2><?= e(t('Maintenance Schedule & History')) ?></h2>
            <form method="get" action="index.php" data-live data-target="#maintList" class="inline-filter"><input type="hidden" name="r" value="maintenance/index">
                <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (Maintenance::STATUSES as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e($s === 'in_progress' ? t('In Maintenance') : status_meta($s)[0]) ?></option><?php endforeach; ?></select></form>
        </div>
        <div id="maintList"><?php include __DIR__ . '/_list.php'; ?></div>
    </section>
</div>
