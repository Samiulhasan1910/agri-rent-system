<div class="alert alert-success emergency-result">
    <?= icon('check-circle') ?>
    <div>
        <strong><?= e(t('Request sent! Reference :code', ['code' => $r['request_code']])) ?></strong>
        <p><?= e(t('Nearest equipment')) ?>: <b><?= e($r['equipment_name']) ?></b> — <?= e(number_format((float)$r['distance_km'], 1)) ?> km (<?= e($r['equipment_location']) ?>)</p>
        <p class="muted small"><?= e(t('Our team has been notified and will contact you shortly.')) ?> <?= $r['owner_phone'] ? e(t('Owner contact')) . ': ' . e($r['owner_phone']) : '' ?></p>
    </div>
</div>
