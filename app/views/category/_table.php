<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Category ID')) ?></th><th><?= e(t('Category name')) ?></th><th><?= e(t('Total equipment')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr data-row="cat-<?= (int)$c['id'] ?>">
                <td>CAT-<?= str_pad((string)$c['id'], 2, '0', STR_PAD_LEFT) ?></td>
                <td><div class="cell-media"><img class="thumb sm" src="assets/img/equipment/<?= e($c['icon']) ?>.svg" alt=""><div><strong><?= e(t($c['name'])) ?></strong><small class="block muted"><?= e($c['description'] ?? '') ?></small></div></div></td>
                <td><?= (int)$c['equipment_count'] ?></td>
                <td><?= badge($c['status']) ?></td>
                <td class="nowrap">
                    <button class="btn btn-light btn-xs" type="button" data-modal-open="#categoryModal" data-category-edit='<?= e(json_encode(['id' => (int)$c['id'], 'name' => $c['name'], 'description' => $c['description'], 'icon' => $c['icon'], 'status' => $c['status']])) ?>'><?= icon('edit', 'icon-xs') ?><?= e(t('Edit')) ?></button>
                    <form class="inline" method="post" action="<?= e(url('category/destroy')) ?>" data-ajax data-confirm="<?= e(t('Delete the category :name?', ['name' => $c['name']])) ?>" data-remove-row="cat-<?= (int)$c['id'] ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button class="btn btn-danger-soft btn-xs" type="submit"><?= icon('trash', 'icon-xs') ?><?= e(t('Remove')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="center muted"><?= e(t('No categories found.')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
