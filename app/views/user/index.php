<div class="page-head">
    <div><h1><?= e(t('User Management')) ?></h1><p class="muted"><?= e(t('Create staff accounts, activate or deactivate users and reset passwords')) ?></p></div>
    <button class="btn btn-primary" type="button" data-modal-open="#userModal"><?= icon('plus') ?><?= e(t('Create account')) ?></button>
</div>
<section class="card">
    <form class="toolbar" method="get" action="index.php" data-live data-target="#userTable">
        <input type="hidden" name="r" value="user/index">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search name, email or phone')) ?>"></div>
        <select name="role"><option value=""><?= e(t('All roles')) ?></option><?php foreach (['farmer', 'employee', 'admin'] as $r): ?><option value="<?= $r ?>"<?= selected($f['role'], $r) ?>><?= e(role_label($r)) ?></option><?php endforeach; ?></select>
        <select name="status"><option value=""><?= e(t('All Status')) ?></option><?php foreach (['active', 'inactive', 'suspended', 'deleted'] as $s): ?><option value="<?= $s ?>"<?= selected($f['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
    </form>
    <div id="userTable"><?php include __DIR__ . '/_table.php'; ?></div>
</section>

<div class="modal" id="userModal" role="dialog" aria-modal="true" hidden>
    <form class="modal-card" method="post" action="<?= e(url('user/store')) ?>" data-ajax novalidate>
        <div class="modal-head"><h3><?= e(t('Create account')) ?></h3><button type="button" class="btn-icon" data-modal-close>×</button></div>
        <div class="modal-body">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field"><label><?= e(t('Full Name')) ?></label><input name="name" required><?= field_error('name') ?></div>
                <div class="field"><label><?= e(t('Email')) ?></label><input name="email" type="email" required><?= field_error('email') ?></div>
                <div class="field"><label><?= e(t('Phone')) ?></label><input name="phone" placeholder="01XXXXXXXXX" required><?= field_error('phone') ?></div>
                <div class="field"><label><?= e(t('User Type')) ?></label><select name="role"><option value="employee"><?= e(t('Employee')) ?></option><option value="admin"><?= e(t('Admin')) ?></option><option value="farmer"><?= e(t('Farmer')) ?></option></select><?= field_error('role') ?></div>
                <div class="field span-2"><label><?= e(t('Address')) ?></label><input name="address"></div>
            </div>
            <p class="muted small"><?= icon('info', 'icon-xs') ?> <?= e(t('The user receives an email link to set their own password.')) ?></p>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><?= e(t('Create')) ?></button></div>
    </form>
</div>
