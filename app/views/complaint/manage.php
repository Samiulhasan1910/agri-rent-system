<div class="page-head"><div><h1><?= e(t('Manage Complaints')) ?></h1><p class="muted"><?= e(t('Review support tickets and update their status. Complaint details are visible to authorised staff only.')) ?></p></div></div>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#complaintTable">
        <input type="hidden" name="r" value="complaint/index">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search ticket, subject or user')) ?>"></div>
        <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (Complaint::STATUSES as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
        <select name="category"><option value=""><?= e(t('All Categories')) ?></option><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"<?= selected($f['category'], $c) ?>><?= e(t($c)) ?></option><?php endforeach; ?></select>
        <select name="priority"><option value=""><?= e(t('All priorities')) ?></option><?php foreach (['high', 'medium', 'low'] as $p): ?><option value="<?= $p ?>"<?= selected($f['priority'], $p) ?>><?= e(status_meta($p)[0]) ?></option><?php endforeach; ?></select>
    </form>
    <div id="complaintTable"><?php include __DIR__ . '/_table.php'; ?></div>
</section>

<div class="modal" id="complaintModal" role="dialog" aria-modal="true" hidden>
    <form class="modal-card" method="post" action="<?= e(url('complaint/update')) ?>" data-ajax data-complaint-form novalidate>
        <div class="modal-head"><h3 data-modal-title><?= e(t('Update Status')) ?></h3><button type="button" class="btn-icon" data-modal-close>×</button></div>
        <div class="modal-body">
            <?= csrf_field() ?><input type="hidden" name="id" value="">
            <div class="complaint-detail" data-complaint-detail></div>
            <div class="field"><label><?= e(t('Status')) ?></label><select name="status"><?php foreach (Complaint::STATUSES as $s): ?><option value="<?= $s ?>"><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label><?= e(t('Response to user')) ?></label><textarea name="response" rows="3"></textarea></div>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><?= e(t('Update Status')) ?></button></div>
    </form>
</div>
