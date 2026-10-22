<div class="page-head"><div><h1><?= e(t('Return Inspection')) ?></h1><p class="muted"><?= e(t('Inspect returned equipment to record its condition. Damaged equipment is marked unavailable automatically.')) ?></p></div></div>
<section class="card">
    <div class="card-head"><h2><?= e(t('Return List')) ?> <small class="muted">(<?= count($returns) ?>)</small></h2></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Equipment Name')) ?></th><th><?= e(t('Return Date')) ?></th><th><?= e(t('Farmer Name')) ?></th><th><?= e(t('Select Condition')) ?></th><th><?= e(t('Usage hours')) ?></th><th><?= e(t('Comment')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($returns as $r): $fid = 'insp-' . (int)$r['id']; ?>
                <tr data-row="ret-<?= (int)$r['id'] ?>">
                    <td><strong><?= e($r['booking_code']) ?></strong></td>
                    <td><?= e($r['equipment_name']) ?></td>
                    <td><?= e(fmt_date($r['end_date'])) ?></td>
                    <td><?= e($r['farmer_name']) ?></td>
                    <td><select name="condition" form="<?= $fid ?>" required data-condition-select><option value=""><?= e(t('Select')) ?></option><?php foreach (Inspection::CONDITIONS as $k => $label): ?><option value="<?= $k ?>"><?= e(t($label)) ?></option><?php endforeach; ?></select></td>
                    <td><input type="number" min="0" step="0.5" name="usage_hours" form="<?= $fid ?>" placeholder="h" style="width:80px"></td>
                    <td><input name="comments" form="<?= $fid ?>" placeholder="<?= e(t('Optional notes')) ?>"></td>
                    <td><form id="<?= $fid ?>" method="post" action="<?= e(url('inspection/store')) ?>" data-ajax data-remove-row="ret-<?= (int)$r['id'] ?>"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-primary btn-xs" type="submit"><?= e(t('Submit')) ?></button></form></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$returns): ?><tr><td colspan="8"><?php $icon = 'clipboard-check'; $heading = t('No equipment waiting for inspection.'); $text = t('Returned rentals will appear here.'); include __DIR__ . '/../partials/empty.php'; ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="card">
    <h2 class="card-title"><?= e(t('Inspection History')) ?></h2>
    <div class="table-wrap"><table class="table compact">
        <thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Booking ID')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Farmer')) ?></th><th><?= e(t('Condition')) ?></th><th><?= e(t('Hours')) ?></th><th><?= e(t('Comment')) ?></th><th><?= e(t('Inspector')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): ?><tr><td><?= e(fmt_date($h['created_at'])) ?></td><td><?= e($h['booking_code']) ?></td><td><?= e($h['equipment_name']) ?></td><td><?= e($h['farmer_name']) ?></td><td><?= badge($h['condition']) ?></td><td><?= $h['usage_hours'] === null ? '—' : e((string)$h['usage_hours']) ?></td><td><small><?= e($h['comments'] ?? '—') ?></small></td><td><?= e($h['inspector_name']) ?></td></tr><?php endforeach; ?>
        </tbody>
    </table></div>
</section>
