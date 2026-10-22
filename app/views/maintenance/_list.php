<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Equipment')) ?></th><th><?= e(t('Maintenance Date')) ?></th><th><?= e(t('Type')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $m): ?>
            <tr>
                <td><strong><?= e($m['equipment_name']) ?></strong><small class="block muted"><?= e(str_limit($m['notes'], 60)) ?></small></td>
                <td><?= e(fmt_date($m['scheduled_date'])) ?><?= $m['end_date'] !== $m['scheduled_date'] ? ' – ' . e(fmt_date($m['end_date'])) : '' ?></td>
                <td><?= e(t(ucfirst($m['type']))) ?></td>
                <td><?= badge($m['status'], $m['status'] === 'in_progress' ? t('In Maintenance') : null) ?></td>
                <td class="nowrap">
                    <?php if ($m['status'] === 'scheduled'): ?>
                        <form class="inline" method="post" action="<?= e(url('maintenance/status')) ?>" data-ajax data-reload><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="status" value="in_progress"><button class="btn btn-outline btn-xs"><?= e(t('Start')) ?></button></form>
                    <?php endif; ?>
                    <?php if (in_array($m['status'], ['scheduled', 'in_progress'], true)): ?>
                        <form class="inline" method="post" action="<?= e(url('maintenance/status')) ?>" data-ajax data-reload><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="status" value="completed"><button class="btn btn-primary btn-xs"><?= e(t('Complete')) ?></button></form>
                        <form class="inline" method="post" action="<?= e(url('maintenance/status')) ?>" data-ajax data-reload data-confirm="<?= e(t('Cancel this maintenance?')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="status" value="cancelled"><button class="btn btn-light btn-xs"><?= e(t('Cancel')) ?></button></form>
                    <?php else: ?><small class="muted"><?= e($m['completed_at'] ? fmt_date($m['completed_at']) : '—') ?></small><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="center muted"><?= e(t('No maintenance records.')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
