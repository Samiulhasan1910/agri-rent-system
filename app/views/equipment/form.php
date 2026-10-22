<?php
$v = static fn(string $key, $default = '') => old($key, $item[$key] ?? $default);
$editing = $item !== null;
?>
<div class="page-head">
    <div><nav class="crumbs"><a href="<?= e(url('equipment/manage')) ?>"><?= e(t('Manage Equipment')) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e($title) ?></span></nav><h1><?= e($title) ?></h1></div>
</div>
<form class="card form-card" method="post" action="<?= e(url($editing ? 'equipment/update' : 'equipment/store')) ?>" enctype="multipart/form-data" data-ajax novalidate>
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>
    <div class="equipment-form">
        <div class="image-upload">
            <label class="upload-box" for="eq-image">
                <img src="<?= $editing ? e(equipment_image($item)) : '' ?>" alt="" data-preview <?= $editing ? '' : 'hidden' ?>>
                <span class="upload-hint" <?= $editing ? 'hidden' : '' ?>><?= icon('upload') ?><?= e(t('Click to upload image')) ?><small>JPG, PNG, WEBP · <?= (int)config('uploads.max_image_mb', 5) ?> MB</small></span>
            </label>
            <input class="visually-hidden" id="eq-image" type="file" name="image" accept="image/*" data-image-input>
            <?= field_error('image') ?>
            <?php if ($editing): ?><small class="muted"><?= e(t('Leave empty to keep the current image.')) ?></small><?php endif; ?>
        </div>
        <div class="form-grid">
            <div class="field span-2"><label for="f-name"><?= e(t('Equipment Name')) ?> *</label><input id="f-name" name="name" value="<?= e($v('name')) ?>" placeholder="<?= e(t('e.g. Tractor 45 HP')) ?>" required><?= field_error('name') ?></div>
            <div class="field"><label for="f-cat"><?= e(t('Category')) ?> *</label><select id="f-cat" name="category_id" required><option value=""><?= e(t('Select category')) ?></option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($v('category_id'), $c['id']) ?>><?= e(t($c['name'])) ?></option><?php endforeach; ?></select><?= field_error('category_id') ?></div>
            <div class="field"><label for="f-specs"><?= e(t('Specifications')) ?></label><input id="f-specs" name="specs" value="<?= e($v('specs')) ?>" placeholder="<?= e(t('e.g. 45 HP | 4 Wheel Drive')) ?>"></div>
            <div class="field"><label for="f-price"><?= e(t('Price')) ?> (৳ / <?= e(t('day')) ?>) *</label><input id="f-price" name="price_per_day" inputmode="decimal" value="<?= e($v('price_per_day')) ?>" placeholder="000" required><?= field_error('price_per_day') ?></div>
            <div class="field"><label for="f-qty"><?= e(t('Quantity')) ?> *</label><input id="f-qty" type="number" min="1" name="quantity" value="<?= e($v('quantity', 1)) ?>" required><?= field_error('quantity') ?></div>
            <div class="field"><label for="f-loc"><?= e(t('Location')) ?> *</label><input id="f-loc" name="location" list="eq-locations" value="<?= e($v('location')) ?>" placeholder="<?= e(t('District')) ?>" required><datalist id="eq-locations"><?php foreach ($locations as $l): ?><option value="<?= e($l) ?>"><?php endforeach; ?></datalist><?= field_error('location') ?></div>
            <div class="field"><label for="f-status"><?= e(t('Status')) ?></label><select id="f-status" name="status"><?php foreach (['available', 'unavailable', 'maintenance'] as $s): ?><option value="<?= $s ?>"<?= selected($v('status', 'available'), $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="f-owner"><?= e(t('Owner / Service provider')) ?></label><input id="f-owner" name="owner_name" value="<?= e($v('owner_name')) ?>"></div>
            <div class="field"><label for="f-ophone"><?= e(t('Owner phone')) ?></label><input id="f-ophone" name="owner_phone" value="<?= e($v('owner_phone')) ?>"></div>
            <div class="field"><label for="f-thr"><?= e(t('Service threshold (usage hours)')) ?></label><input id="f-thr" type="number" min="10" name="usage_threshold_hours" value="<?= e($v('usage_threshold_hours', setting('default_usage_threshold', 250))) ?>"><?= field_error('usage_threshold_hours') ?></div>
            <div class="field"><label for="f-int"><?= e(t('Service interval (days)')) ?></label><input id="f-int" type="number" min="7" name="service_interval_days" value="<?= e($v('service_interval_days', 120)) ?>"><?= field_error('service_interval_days') ?></div>
            <?php if ($editing): ?><div class="field span-2"><label for="f-note"><?= e(t('Reason for price change (audit log)')) ?></label><input id="f-note" name="price_note" placeholder="<?= e(t('Optional')) ?>"></div><?php endif; ?>
            <div class="field span-2"><label for="f-desc"><?= e(t('Description')) ?> *</label><textarea id="f-desc" name="description" rows="5" required><?= e($v('description')) ?></textarea><?= field_error('description') ?></div>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn btn-light" href="<?= e(url('equipment/manage')) ?>"><?= e(t('Cancel')) ?></a>
        <?php if (!$editing): ?><button class="btn btn-outline" type="reset"><?= e(t('Clear Form')) ?></button><?php endif; ?>
        <button class="btn btn-primary" type="submit"><?= icon($editing ? 'check' : 'plus') ?><?= e($editing ? t('Update Equipment') : t('Add Equipment')) ?></button>
    </div>
</form>
