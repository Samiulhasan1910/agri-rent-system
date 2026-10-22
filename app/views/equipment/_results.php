<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= icon('alert') ?><span><?= e($error) ?></span></div>
<?php elseif ($systemEmpty): ?>
    <?php $icon = 'tractor'; $heading = t('No equipment available'); $text = t('There is no equipment in the system yet. Please check back soon.'); include __DIR__ . '/../partials/empty.php'; ?>
<?php elseif (!$rows): ?>
    <?php $icon = 'search'; $heading = t('No equipment found'); $text = t('Try another keyword, category, location or price range.'); $action = '<a class="btn btn-outline btn-sm" href="' . e(url('equipment/index')) . '">' . e(t('Clear filters')) . '</a>'; include __DIR__ . '/../partials/empty.php'; unset($action); ?>
<?php else: ?>
    <div class="equipment-grid">
        <?php
        $freeMap = is_array($free ?? null) ? $free : [];
        $heart = is_role('farmer');
        foreach ($rows as $e) {
            $free = $freeMap[(int)$e['id']] ?? null;
            $saved = in_array((int)$e['id'], $wishlist ?? [], true);
            include __DIR__ . '/../partials/eq_card.php';
        }
        $free = $freeMap;
        ?>
    </div>
    <?php include __DIR__ . '/../partials/pagination.php'; ?>
<?php endif; ?>
