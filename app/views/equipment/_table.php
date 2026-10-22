<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Image')) ?></th><th><?= e(t('Equipment Name')) ?></th><th><?= e(t('Category')) ?></th><th><?= e(t('Price/Day')) ?></th><th><?= e(t('Quantity')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Health')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr data-row="eq-<?= (int)$r['id'] ?>">
                <td><img class="thumb" src="<?= e(equipment_image($r)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($r['category_icon']) ?>.svg'"></td>
                <td><a href="<?= e(url('equipment/show', ['id' => $r['id']])) ?>"><strong><?= e($r['name']) ?></strong></a><small class="block muted"><?= icon('map-pin', 'icon-xs') ?><?= e($r['location']) ?></small></td>
                <td><?= e(t($r['category_name'])) ?></td>
                <td><?= $r['price_per_day'] !== null ? money($r['price_per_day']) : '<span class="text-danger">' . e(t('Missing')) . '</span>' ?></td>
                <td><?= (int)$r['quantity'] ?></td>
                <td><?= badge($r['status']) ?></td>
                <td><?= badge($r['health_status']) ?></td>
                <td class="nowrap">
                    <a class="btn btn-light btn-xs" href="<?= e(url('equipment/edit', ['id' => $r['id']])) ?>"><?= icon('edit', 'icon-xs') ?><?= e(t('Edit')) ?></a>
                    <?php if (is_role('admin')): ?>
                        <form class="inline" method="post" action="<?= e(url('equipment/destroy')) ?>" data-ajax data-confirm="<?= e(t('Remove :name from the equipment list?', ['name' => $r['name']])) ?>" data-remove-row="eq-<?= (int)$r['id'] ?>">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-danger-soft btn-xs" type="submit"><?= icon('trash', 'icon-xs') ?><?= e(t('Remove')) ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8"><?php $icon = 'tractor'; $heading = t('No equipment found'); $text = t('Try a different filter or add new equipment.'); include __DIR__ . '/../partials/empty.php'; ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
