<div class="page-head">
    <div><h1><?= e(t('Delivery Management')) ?></h1><p class="muted"><?= e(t('Monitor equipment delivery, return pickup and delivery team status')) ?></p></div>
    <button class="btn btn-outline" type="button" data-modal-open="#teamModal" data-team-new><?= icon('users') ?><?= e(t('Delivery Teams')) ?></button>
</div>
<div class="chips status-summary">
    <?php foreach ($counts as $s => $n): ?><a class="chip <?= $f['status'] === $s ? 'active' : '' ?>" href="<?= e(url('delivery/index', ['status' => $s])) ?>"><?= e(status_meta($s)[0]) ?> <b><?= (int)$n ?></b></a><?php endforeach; ?>
    <a class="chip <?= $f['status'] === '' ? 'active' : '' ?>" href="<?= e(url('delivery/index')) ?>"><?= e(t('All')) ?></a>
</div>
<div class="split-panel">
    <section class="card">
        <form class="toolbar" method="get" action="index.php" data-live data-target="#deliveryTable">
            <input type="hidden" name="r" value="delivery/index">
            <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search booking, farmer or address...')) ?>"></div>
            <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (['pending', 'assigned', 'in_progress', 'delivered', 'returned'] as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
            <select name="team"><option value=""><?= e(t('All teams')) ?></option><?php foreach ($teams as $tm): ?><option value="<?= (int)$tm['id'] ?>"<?= selected($f['team'], $tm['id']) ?>><?= e($tm['name']) ?></option><?php endforeach; ?></select>
            <input type="date" name="date" value="<?= e($f['date']) ?>" aria-label="<?= e(t('Delivery date')) ?>">
        </form>
        <div id="deliveryTable"><?php include __DIR__ . '/_table.php'; ?></div>
    </section>
    <aside class="card side-panel" id="deliveryPanel" aria-live="polite">
        <?php if ($selected): $d = $selected; $teams_ = $teams; ?>
            <?php $teams = array_filter($teams_, static fn($t) => $t['status'] === 'active'); include __DIR__ . '/_panel.php'; $teams = $teams_; ?>
        <?php else: ?><p class="muted center"><?= e(t('Select a delivery to see details.')) ?></p><?php endif; ?>
    </aside>
</div>

<section class="card">
    <div class="card-head"><h2><?= icon('users') ?> <?= e(t('Delivery Teams')) ?></h2></div>
    <div class="team-grid">
        <?php foreach ($teams as $tm): ?>
            <article class="team-card">
                <div class="team-top"><strong><?= e($tm['name']) ?></strong><?= badge($tm['status']) ?></div>
                <small><?= icon('user', 'icon-xs') ?><?= e($tm['leader_name']) ?></small>
                <small><?= icon('phone', 'icon-xs') ?><a href="tel:<?= e($tm['phone']) ?>"><?= e($tm['phone']) ?></a></small>
                <small><?= icon('mail', 'icon-xs') ?><a href="mailto:<?= e($tm['email']) ?>"><?= e($tm['email']) ?></a></small>
                <small><?= icon('truck', 'icon-xs') ?><?= e($tm['vehicle'] ?: '—') ?></small>
                <div class="team-foot"><span class="pill"><?= (int)$tm['active_jobs'] ?> <?= e(t('active jobs')) ?></span>
                    <button class="btn btn-light btn-xs" type="button" data-modal-open="#teamModal" data-team-edit='<?= e(json_encode($tm)) ?>'><?= icon('edit', 'icon-xs') ?><?= e(t('Edit')) ?></button></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="modal" id="teamModal" role="dialog" aria-modal="true" hidden>
    <form class="modal-card" method="post" action="<?= e(url('delivery/team-save')) ?>" data-ajax data-team-form novalidate>
        <div class="modal-head"><h3 data-modal-title><?= e(t('Add delivery team')) ?></h3><button type="button" class="btn-icon" data-modal-close>×</button></div>
        <div class="modal-body">
            <?= csrf_field() ?><input type="hidden" name="id" value="">
            <div class="form-grid">
                <div class="field"><label><?= e(t('Team name')) ?></label><input name="name" required><?= field_error('name') ?></div>
                <div class="field"><label><?= e(t('Team leader')) ?></label><input name="leader_name" required><?= field_error('leader_name') ?></div>
                <div class="field"><label><?= e(t('Phone')) ?></label><input name="phone" required><?= field_error('phone') ?></div>
                <div class="field"><label><?= e(t('Email')) ?></label><input name="email" type="email" required><?= field_error('email') ?></div>
                <div class="field"><label><?= e(t('Vehicle')) ?></label><input name="vehicle"></div>
                <div class="field"><label><?= e(t('Status')) ?></label><select name="status"><option value="active"><?= e(t('Active')) ?></option><option value="inactive"><?= e(t('Inactive')) ?></option></select></div>
            </div>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
    </form>
</div>
