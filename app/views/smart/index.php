<div class="page-head"><div><h1><?= icon('sparkles') ?> <?= e(t('Smart Equipment Matching')) ?></h1><p class="muted"><?= e(t('Tell us about your farm and we will suggest the most suitable equipment.')) ?></p></div></div>
<div class="grid-1-2">
    <section class="card">
        <h2 class="card-title"><?= e(t('Your farm')) ?></h2>
        <form id="smartForm" method="get" action="index.php" data-smart novalidate>
            <input type="hidden" name="r" value="smart/suggest">
            <div class="field"><label for="fs"><?= e(t('Farm Size (acres)')) ?></label><input id="fs" type="number" min="0.1" step="0.1" name="farm_size" placeholder="<?= e(t('e.g. 3.5')) ?>" required><?= field_error('farm_size') ?></div>
            <div class="field"><label for="crop"><?= e(t('Crop Type')) ?></label><input id="crop" name="crop" list="crops" placeholder="<?= e(t('e.g. Rice')) ?>" required><datalist id="crops"><?php foreach ($crops as $c): ?><option value="<?= e($c) ?>"><?= e(t($c)) ?></option><?php endforeach; ?></datalist></div>
            <div class="field"><label for="season"><?= e(t('Season / Farming stage')) ?></label><select id="season" name="season"><option value=""><?= e(t('Select season')) ?></option><?php foreach ($seasons as $k => $l): ?><option value="<?= $k ?>"><?= e(t($l)) ?></option><?php endforeach; ?></select></div>
            <p class="muted small"><?= icon('info', 'icon-xs') ?> <?= e(t('Suggestions update automatically as you type.')) ?></p>
        </form>
        <div class="crop-chips"><?php foreach (array_slice($crops, 0, 8) as $c): ?><button type="button" class="chip" data-fill-crop="<?= e($c) ?>"><?= e(t($c)) ?></button><?php endforeach; ?></div>
    </section>
    <section class="card">
        <div class="card-head"><h2><?= e(t('Suggestion List')) ?></h2><span class="muted small" data-smart-count></span></div>
        <div id="smartResults" class="smart-results is-inactive">
            <div class="smart-placeholder"><?= icon('sparkles') ?><p data-smart-msg><?= e(t('Enter farm size, crop type and season to see suggestions.')) ?></p></div>
        </div>
    </section>
</div>

<?php if ($rules): ?>
<section class="card">
    <div class="card-head"><h2><?= e(t('Matching rules')) ?> <small class="muted">(<?= count($rules) ?>)</small></h2><button class="btn btn-primary btn-sm" type="button" data-modal-open="#ruleModal"><?= icon('plus') ?><?= e(t('Add rule')) ?></button></div>
    <div class="table-wrap"><table class="table compact">
        <thead><tr><th><?= e(t('Crop')) ?></th><th><?= e(t('Season')) ?></th><th><?= e(t('Category')) ?></th><th><?= e(t('Priority')) ?></th><th><?= e(t('Farm size')) ?></th><th><?= e(t('Note')) ?></th><th></th></tr></thead>
        <tbody><?php foreach ($rules as $r): ?><tr data-row="rule-<?= (int)$r['id'] ?>"><td><?= e(t($r['crop'])) ?></td><td><?= e(t(CropRule::SEASONS[$r['season']])) ?></td><td><?= e(t($r['category_name'])) ?></td><td><?= (int)$r['priority'] ?></td><td><?= e((float)$r['min_farm_size'] . ' – ' . ($r['max_farm_size'] === null ? '∞' : (float)$r['max_farm_size'])) ?></td><td><small><?= e($r['note'] ?? '') ?></small></td>
            <td><form method="post" action="<?= e(url('smart/rule-delete')) ?>" data-ajax data-remove-row="rule-<?= (int)$r['id'] ?>" data-confirm="<?= e(t('Delete this rule?')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn-icon"><?= icon('trash', 'icon-xs') ?></button></form></td></tr><?php endforeach; ?></tbody>
    </table></div>
</section>
<div class="modal" id="ruleModal" role="dialog" aria-modal="true" hidden>
    <form class="modal-card" method="post" action="<?= e(url('smart/rule-save')) ?>" data-ajax novalidate>
        <div class="modal-head"><h3><?= e(t('Add rule')) ?></h3><button type="button" class="btn-icon" data-modal-close>×</button></div>
        <div class="modal-body form-grid">
            <?= csrf_field() ?>
            <div class="field"><label><?= e(t('Crop')) ?></label><input name="crop" list="crops" required><?= field_error('crop') ?></div>
            <div class="field"><label><?= e(t('Season')) ?></label><select name="season"><?php foreach ($seasons as $k => $l): ?><option value="<?= $k ?>"><?= e(t($l)) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label><?= e(t('Category')) ?></label><select name="category_id"><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e(t($c['name'])) ?></option><?php endforeach; ?></select><?= field_error('category_id') ?></div>
            <div class="field"><label><?= e(t('Priority (1-100)')) ?></label><input type="number" name="priority" value="70" min="1" max="100"><?= field_error('priority') ?></div>
            <div class="field"><label><?= e(t('Min farm size')) ?></label><input type="number" step="0.1" name="min_farm_size" value="0"></div>
            <div class="field"><label><?= e(t('Max farm size')) ?></label><input type="number" step="0.1" name="max_farm_size"></div>
            <div class="field span-2"><label><?= e(t('Note')) ?></label><input name="note"></div>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
    </form>
</div>
<?php endif; ?>
