<div class="page-head"><div><h1><?= icon('siren') ?> <?= e(t('Emergency Requests')) ?></h1><p class="muted"><?= e(t('Urgent requests matched to the nearest available equipment.')) ?></p></div></div>
<div class="chips">
    <?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $k => $l): ?><a class="chip <?= $status === $k ? 'active' : '' ?>" href="<?= e(url('emergency/index', $k ? ['status' => $k] : [])) ?>"><?= e(t($l)) ?></a><?php endforeach; ?>
</div>
<section class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th><?= e(t('Request')) ?></th><th><?= e(t('Farmer')) ?></th><th><?= e(t('Equipment needed')) ?></th><th><?= e(t('Nearest match')) ?></th><th><?= e(t('Needed by')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= e($r['request_code']) ?></strong><small class="block muted"><?= e(time_ago($r['created_at'])) ?></small></td>
                <td><?= e($r['farmer_name']) ?><small class="block"><a href="tel:<?= e($r['farmer_phone']) ?>"><?= e($r['farmer_phone']) ?></a></small></td>
                <td><?= e(t($r['category_name'])) ?><small class="block muted"><?= e(str_limit($r['details'] ?? '', 50)) ?></small></td>
                <td><?= e($r['equipment_name'] ?? '—') ?><small class="block muted"><?= e($r['location_label']) ?> · <?= e(number_format((float)$r['distance_km'], 1)) ?> km · <a href="https://www.google.com/maps?q=<?= e($r['latitude'] . ',' . $r['longitude']) ?>" target="_blank" rel="noopener"><?= e(t('Map')) ?></a></small></td>
                <td><?= e(fmt_datetime($r['needed_by'])) ?></td>
                <td><?= badge($r['status']) ?></td>
                <td class="nowrap">
                    <?php if ($r['status'] === 'pending'): ?>
                        <form class="inline" method="post" action="<?= e(url('emergency/status')) ?>" data-ajax data-reload><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="approved"><button class="btn btn-primary btn-xs"><?= e(t('Approve')) ?></button></form>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'approved'): ?>
                        <form class="inline" method="post" action="<?= e(url('emergency/status')) ?>" data-ajax data-reload><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="completed"><button class="btn btn-outline btn-xs"><?= e(t('Complete')) ?></button></form>
                    <?php endif; ?>
                    <?php if (in_array($r['status'], ['pending', 'approved'], true)): ?>
                        <form class="inline" method="post" action="<?= e(url('emergency/status')) ?>" data-ajax data-reload data-confirm="<?= e(t('Reject this emergency request?')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="rejected"><button class="btn btn-danger-soft btn-xs"><?= e(t('Reject')) ?></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="center muted"><?= e(t('No emergency requests.')) ?></td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
