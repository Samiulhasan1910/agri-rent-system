<?php $next = Delivery::NEXT[$d['status']] ?? []; ?>
<div class="panel-head"><h2><?= e(t('Delivery Details')) ?></h2><?= badge($d['status']) ?></div>
<dl class="kv">
    <div><dt><?= e(t('Booking ID')) ?></dt><dd><a href="<?= e(url('booking/show', ['id' => $d['booking_id']])) ?>"><?= e($d['booking_code']) ?></a> <?= badge($d['booking_status']) ?></dd></div>
    <div><dt><?= e(t('Equipment')) ?></dt><dd><?= e($d['equipment_name']) ?></dd></div>
    <div><dt><?= e(t('Farmer')) ?></dt><dd><?= e($d['farmer_name']) ?></dd></div>
    <div><dt><?= icon('phone', 'icon-xs') ?><?= e(t('Phone')) ?></dt><dd><a href="tel:<?= e($d['farmer_phone']) ?>"><?= e($d['farmer_phone']) ?></a></dd></div>
    <div><dt><?= icon('map-pin', 'icon-xs') ?><?= e(t('Address')) ?></dt><dd><?= e($d['address']) ?></dd></div>
    <div><dt><?= e(t('Rental')) ?></dt><dd><?= e(fmt_date($d['start_date'])) ?> – <?= e(fmt_date($d['end_date'])) ?></dd></div>
    <div><dt><?= icon('truck', 'icon-xs') ?><?= e(t('Delivery Team')) ?></dt><dd><?= e($d['team_name'] ?? t('Not assigned')) ?></dd></div>
    <?php if ($d['team_name']): ?>
        <div><dt><?= e(t('Team Contact')) ?></dt><dd><?= e($d['team_leader']) ?> · <a href="tel:<?= e($d['team_phone']) ?>"><?= e($d['team_phone']) ?></a><small class="block"><a href="mailto:<?= e($d['team_email']) ?>"><?= e($d['team_email']) ?></a></small></dd></div>
    <?php endif; ?>
    <div><dt><?= icon('clock', 'icon-xs') ?><?= e(t('Estimated Delivery Time')) ?></dt><dd><?= e(fmt_date($d['scheduled_date'])) ?> <?= e($d['estimated_time'] ?? '') ?></dd></div>
</dl>
<div class="progress-bar"><span style="width:<?= (int)$d['progress'] ?>%"></span></div>
<ul class="timeline compact">
    <?php foreach ($updates as $u): ?><li class="done"><b><?= e(status_meta($u['status'])[0]) ?></b><small><?= e(fmt_datetime($u['created_at'])) ?><?= $u['note'] ? ' · ' . e($u['note']) : '' ?></small></li><?php endforeach; ?>
    <?php if (!$updates): ?><li><b><?= e(t('Waiting for delivery assignment')) ?></b></li><?php endif; ?>
</ul>

<?php if (!in_array($d['status'], ['delivered', 'returned', 'cancelled'], true)): ?>
    <form class="stack" method="post" action="<?= e(url('delivery/assign')) ?>" data-ajax data-panel-update novalidate>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
        <h3 class="sub-title"><?= e($d['team_id'] ? t('Reassign Delivery') : t('Assign Delivery')) ?></h3>
        <select name="team_id" required><option value=""><?= e(t('Select team')) ?></option><?php foreach ($teams as $tm): ?><option value="<?= (int)$tm['id'] ?>"<?= selected($d['team_id'], $tm['id']) ?>><?= e($tm['name']) ?> (<?= e($tm['leader_name']) ?>)</option><?php endforeach; ?></select>
        <?= field_error('team_id') ?>
        <div class="form-grid"><input type="date" name="scheduled_date" value="<?= e($d['scheduled_date'] ?: $d['start_date']) ?>" required><input name="estimated_time" value="<?= e($d['estimated_time'] ?? '10:00 AM') ?>" placeholder="10:00 AM"></div>
        <button class="btn btn-outline btn-sm" type="submit"><?= icon('truck') ?><?= e(t('Assign Delivery')) ?></button>
    </form>
<?php endif; ?>
<?php if ($next && $d['team_id']): ?>
    <div class="stack">
        <?php foreach ($next as $to): ?>
            <form method="post" action="<?= e(url('delivery/status')) ?>" data-ajax data-panel-update>
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><input type="hidden" name="status" value="<?= $to ?>">
                <button class="btn <?= $to === 'delivered' ? 'btn-primary' : 'btn-outline' ?> btn-block" type="submit"><?= icon($to === 'delivered' ? 'check-circle' : ($to === 'returned' ? 'history' : 'truck')) ?><?= e(['in_progress' => t('Update Status: In Progress'), 'delivered' => t('Mark as Delivered'), 'returned' => t('Mark as Returned')][$to]) ?></button>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
