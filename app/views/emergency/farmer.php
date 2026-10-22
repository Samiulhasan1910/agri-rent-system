<div class="page-head"><div><h1><?= icon('siren') ?> <?= e(t('Emergency Equipment Request')) ?></h1><p class="muted"><?= e(t('Need equipment urgently? We will find the nearest available machine within :km km.', ['km' => (int)$radius])) ?></p></div></div>
<div class="grid-1-2">
    <section class="card emergency-card">
        <form method="post" action="<?= e(url('emergency/store')) ?>" data-emergency data-uuid novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="latitude"><input type="hidden" name="longitude"><input type="hidden" name="client_uuid">
            <div class="field"><label for="em-cat"><?= e(t('Equipment needed')) ?></label><select id="em-cat" name="category_id" required><option value=""><?= e(t('Select equipment type')) ?></option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e(t($c['name'])) ?></option><?php endforeach; ?></select><?= field_error('category_id') ?></div>
            <div class="field"><label for="em-need"><?= e(t('Needed by')) ?></label><input id="em-need" type="datetime-local" name="needed_by" value="<?= e(date('Y-m-d\TH:i', time() + 6 * 3600)) ?>"><?= field_error('needed_by') ?></div>
            <div class="field"><label for="em-det"><?= e(t('Situation details')) ?></label><textarea id="em-det" name="details" rows="3" maxlength="500" placeholder="<?= e(t('e.g. field flooding, need a pump within hours')) ?>"></textarea></div>
            <div class="location-box" data-location-box>
                <div class="loc-status" data-loc-status><?= icon('crosshair') ?><span><?= e(t('Your location will be detected when you press the emergency button.')) ?></span></div>
                <div class="field" data-district-fallback hidden>
                    <label for="em-dist"><?= e(t('Or choose your district')) ?></label>
                    <select id="em-dist" name="district"><option value=""><?= e(t('Select district')) ?></option><?php foreach ($districts as $d): ?><option><?= e($d) ?></option><?php endforeach; ?></select>
                </div>
            </div>
            <button class="emergency-btn" type="submit"><?= icon('siren') ?><span><?= e(t('Send Emergency Request')) ?></span></button>
        </form>
        <div id="emergencyResult" aria-live="polite"></div>
    </section>
    <section class="card">
        <h2 class="card-title"><?= e(t('My emergency requests')) ?></h2>
        <div class="list">
            <?php foreach ($rows as $r): ?>
                <div class="list-item">
                    <span class="li-icon red"><?= icon('siren') ?></span>
                    <div><strong><?= e($r['request_code']) ?> · <?= e(t($r['category_name'])) ?></strong>
                        <small><?= e($r['equipment_name'] ? t(':equipment (:km km away)', ['equipment' => $r['equipment_name'], 'km' => number_format((float)$r['distance_km'], 1)]) : t('No match')) ?></small>
                        <small class="muted"><?= e($r['location_label']) ?> · <?= e(t('Needed by')) ?> <?= e(fmt_datetime($r['needed_by'])) ?></small></div>
                    <?= badge($r['status']) ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$rows): ?><p class="muted center"><?= e(t('You have no emergency requests.')) ?></p><?php endif; ?>
        </div>
    </section>
</div>
