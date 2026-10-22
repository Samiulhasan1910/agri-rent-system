<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Equipment')) ?></th><th><?= e(t('Health')) ?></th><th><?= e(t('Usage since service')) ?></th><th><?= e(t('Last service')) ?></th><th><?= e(t('Alerts')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $pct = min(100, (int)round((float)$r['hours_since_service'] / max(1, (float)$r['usage_threshold_hours']) * 100)); ?>
            <tr>
                <td><div class="cell-media"><img class="thumb" src="<?= e(equipment_image($r)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($r['category_icon']) ?>.svg'"><div><strong><?= e($r['name']) ?></strong><small class="block muted"><?= e(t($r['category_name'])) ?> · <?= e(number_format((float)$r['usage_hours'], 1)) ?> h <?= e(t('total')) ?></small></div></div></td>
                <td><?= badge($r['health_status']) ?><?php if ($r['health_flag']): ?><small class="block text-amber"><?= icon('alert', 'icon-xs') ?><?= e($r['health_flag']) ?></small><?php endif; ?></td>
                <td style="min-width:150px"><div class="meter <?= $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warn' : '') ?>"><span style="width:<?= $pct ?>%"></span></div><small><?= e(number_format((float)$r['hours_since_service'], 1)) ?> / <?= (int)$r['usage_threshold_hours'] ?> h</small></td>
                <td><?= e(fmt_date($r['last_service_date'])) ?><small class="block muted"><?= e(t('every :d days', ['d' => $r['service_interval_days']])) ?></small></td>
                <td><?= (int)$r['open_alerts'] ? '<span class="badge badge-red">' . (int)$r['open_alerts'] . '</span>' : '—' ?></td>
                <td><a class="btn btn-light btn-xs" href="<?= e(url('maintenance/index', ['equipment_id' => $r['id']])) ?>"><?= icon('wrench', 'icon-xs') ?><?= e(t('Schedule')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="center muted"><?= e(t('No equipment found')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
