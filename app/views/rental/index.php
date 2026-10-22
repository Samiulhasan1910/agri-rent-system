<div class="page-head">
    <div><h1><?= e(t('Rental Tracking')) ?></h1><p class="muted"><?= e(t('Track all booked rentals and update rental status')) ?></p></div>
    <a class="btn btn-outline" href="<?= e(url('rental/export', array_filter($f))) ?>"><?= icon('download') ?><?= e(t('Export')) ?></a>
</div>
<div class="chips status-summary">
    <?php foreach (['pending', 'confirmed', 'delivered', 'returned', 'completed', 'cancelled', 'rejected'] as $s): ?>
        <a class="chip <?= $f['status'] === $s ? 'active' : '' ?>" href="<?= e(url('rental/index', ['status' => $s])) ?>"><?= e(status_meta($s)[0]) ?> <b><?= (int)$counts[$s] ?></b></a>
    <?php endforeach; ?>
    <a class="chip <?= $f['status'] === '' ? 'active' : '' ?>" href="<?= e(url('rental/index')) ?>"><?= e(t('All')) ?></a>
</div>

<?php if ($extensions): ?>
<section class="card">
    <div class="card-head"><h2><?= icon('calendar') ?> <?= e(t('Pending Extension Requests')) ?></h2></div>
    <div class="table-wrap"><table class="table compact">
        <thead><tr><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Farmer')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Current Return')) ?></th><th><?= e(t('Requested Return')) ?></th><th><?= e(t('Extra')) ?></th><th><?= e(t('Reason')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($extensions as $x): ?>
            <tr data-row="ext-<?= (int)$x['id'] ?>"><td><?= e($x['booking_code']) ?></td><td><?= e($x['farmer_name']) ?></td><td><?= e($x['equipment_name']) ?></td><td><?= e(fmt_date($x['current_end_date'])) ?></td><td><strong><?= e(fmt_date($x['requested_end_date'])) ?></strong></td><td><?= (int)$x['extra_days'] ?> <?= e(t('days')) ?> · <?= money($x['extra_cost']) ?></td><td><small><?= e(str_limit($x['reason'], 60)) ?></small></td>
                <td class="nowrap">
                    <form class="inline" method="post" action="<?= e(url('rental/extension')) ?>" data-ajax data-remove-row="ext-<?= (int)$x['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><input type="hidden" name="decision" value="approved"><button class="btn btn-primary btn-xs"><?= e(t('Approve')) ?></button></form>
                    <form class="inline" method="post" action="<?= e(url('rental/extension')) ?>" data-ajax data-remove-row="ext-<?= (int)$x['id'] ?>" data-prompt="<?= e(t('Reason for rejection (optional)')) ?>" data-prompt-field="note" data-prompt-optional><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><input type="hidden" name="decision" value="rejected"><button class="btn btn-danger-soft btn-xs"><?= e(t('Reject')) ?></button></form>
                </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php endif; ?>

<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#rentalTable">
        <input type="hidden" name="r" value="rental/index">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search booking or farmer name...')) ?>"></div>
        <select name="equipment"><option value=""><?= e(t('All equipment')) ?></option><?php foreach ($equipment as $eq): ?><option value="<?= (int)$eq['id'] ?>"<?= selected($f['equipment'], $eq['id']) ?>><?= e($eq['name']) ?></option><?php endforeach; ?></select>
        <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (['pending', 'confirmed', 'delivered', 'returned', 'completed', 'cancelled', 'rejected'] as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
        <label class="inline-field"><span><?= e(t('From')) ?></span><input type="date" name="from" value="<?= e($f['from']) ?>"></label>
        <label class="inline-field"><span><?= e(t('To')) ?></span><input type="date" name="to" value="<?= e($f['to']) ?>"></label>
    </form>
    <div id="rentalTable"><?php include __DIR__ . '/_table.php'; ?></div>
</section>
