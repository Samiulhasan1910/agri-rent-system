<tr data-row="delivery-<?= (int)$r['id'] ?>" data-delivery="<?= (int)$r['id'] ?>" data-panel-url="<?= e(url('delivery/show', ['id' => $r['id']])) ?>">
    <td><strong><?= e($r['booking_code']) ?></strong><small class="block muted"><?= e(fmt_date($r['scheduled_date'] ?: $r['start_date'])) ?></small></td>
    <td><?= e($r['equipment_name']) ?></td>
    <td><?= e($r['farmer_name']) ?></td>
    <td><small><?= e($r['address']) ?></small></td>
    <td><?= e($r['team_name'] ?? '—') ?></td>
    <td><?= badge($r['status']) ?></td>
    <td><button class="btn <?= $r['status'] === 'pending' ? 'btn-primary' : 'btn-light' ?> btn-xs" type="button" data-open-panel><?= e($r['status'] === 'pending' ? t('Assign') : ($r['status'] === 'returned' ? t('View') : t('Track/Update'))) ?></button></td>
</tr>
