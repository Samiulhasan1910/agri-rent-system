<div class="page-head"><div><h1><?= e(t('Profile')) ?></h1><p class="muted"><?= e(t('Manage your profile information and account settings.')) ?></p></div></div>
<div class="grid-2-1">
    <div>
        <section class="card">
            <h2 class="card-title"><?= e(t('Profile Overview')) ?></h2>
            <div class="profile-overview">
                <form class="avatar-upload" method="post" action="<?= e(url('profile/photo')) ?>" enctype="multipart/form-data" data-ajax data-autosubmit data-profile-form>
                    <?= csrf_field() ?>
                    <span data-profile="avatar"><?= avatar($user, 'xl') ?></span>
                    <label class="avatar-cam" for="photo" title="<?= e(t('Change photo')) ?>"><?= icon('camera') ?></label>
                    <input class="visually-hidden" id="photo" type="file" name="photo" accept="image/*">
                </form>
                <div>
                    <h3 data-profile="name"><?= e($user['name']) ?></h3>
                    <span class="text-green"><?= e(role_label($user['role'])) ?><?= $user['employee_code'] ? ' · ' . e($user['employee_code']) : '' ?></span>
                    <small class="block"><?= icon('mail', 'icon-xs') ?><?= e($user['email']) ?></small>
                    <small class="block"><?= icon('phone', 'icon-xs') ?><span data-profile="phone"><?= e($user['phone']) ?></span></small>
                    <small class="block"><?= icon('map-pin', 'icon-xs') ?><span data-profile="address"><?= e($user['address']) ?></span></small>
                </div>
            </div>
        </section>
        <section class="card">
            <div class="card-head"><h2><?= e(t('Profile Information')) ?></h2><button class="btn btn-light btn-sm" type="button" data-edit-toggle="#profileForm"><?= icon('edit', 'icon-xs') ?><?= e(t('Edit')) ?></button></div>
            <form id="profileForm" class="profile-form is-locked" method="post" action="<?= e(url('profile/update')) ?>" data-ajax data-profile-form novalidate>
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field"><label><?= icon('user', 'icon-xs') ?> <?= e(t('Full Name')) ?></label><input name="name" value="<?= e($user['name']) ?>" readonly required><?= field_error('name') ?></div>
                    <div class="field"><label><?= icon('mail', 'icon-xs') ?> <?= e(t('Email Address')) ?></label><input value="<?= e($user['email']) ?>" disabled><small class="hint"><?= e(t('Email cannot be changed.')) ?></small></div>
                    <div class="field"><label><?= icon('phone', 'icon-xs') ?> <?= e(t('Phone Number')) ?></label><input name="phone" value="<?= e($user['phone']) ?>" readonly required><?= field_error('phone') ?></div>
                    <div class="field"><label><?= icon('users', 'icon-xs') ?> <?= e(t('User Type')) ?></label><input value="<?= e(role_label($user['role'])) ?>" disabled></div>
                    <div class="field span-2"><label><?= icon('map-pin', 'icon-xs') ?> <?= e(t('Address')) ?></label><input name="address" list="p-districts" value="<?= e($user['address']) ?>" readonly required><datalist id="p-districts"><?php foreach ($districts as $d): ?><option value="<?= e($d) ?>"><?php endforeach; ?></datalist><?= field_error('address') ?></div>
                </div>
                <div class="form-actions edit-only"><button class="btn btn-light" type="button" data-edit-cancel><?= e(t('Cancel')) ?></button><button class="btn btn-primary" type="submit"><?= icon('check') ?><?= e(t('Update')) ?></button></div>
                <p class="muted small view-only"><?= icon('info', 'icon-xs') ?> <?= e(t('Click Edit to update your information.')) ?></p>
            </form>
        </section>
        <?php if ($user['role'] !== 'admin'): ?>
        <section class="card" id="verification">
            <div class="card-head"><h2><?= e(t('Account Verification')) ?></h2><span data-verify-badge><?= badge($user['verification_status']) ?></span></div>
            <p class="muted"><?= e($user['role'] === 'farmer' ? t('Upload your National ID (NID) or another official document. An employee will verify it before you can book equipment.') : t('Upload your employee ID or NID. An administrator will verify your account.')) ?></p>
            <?php if ($user['verification_status'] === 'rejected' && $user['verification_note']): ?><div class="alert alert-danger"><?= icon('x-circle') ?><span><?= e(t('Rejected')) ?>: <?= e($user['verification_note']) ?></span></div><?php endif; ?>
            <?php if ($user['verification_document']): ?><p class="small"><?= icon('paperclip', 'icon-xs') ?> <a href="<?= e(url('verification/document', ['id' => $user['id']])) ?>" target="_blank"><?= e(t('View uploaded document')) ?></a></p><?php endif; ?>
            <?php if ($user['verification_status'] !== 'approved'): ?>
                <form class="input-with-btn" method="post" action="<?= e(url('profile/document')) ?>" enctype="multipart/form-data" data-ajax novalidate>
                    <?= csrf_field() ?>
                    <label class="file-drop small grow" for="doc"><?= icon('upload') ?><span data-file-label><?= e(t('Choose PDF, JPG or PNG (max 5 MB)')) ?></span></label>
                    <input class="visually-hidden" id="doc" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" data-file-input>
                    <button class="btn btn-primary" type="submit"><?= e(t('Upload')) ?></button>
                </form>
                <?= field_error('document') ?>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
    <aside>
        <section class="card">
            <h2 class="card-title"><?= e(t('Account Status')) ?></h2>
            <div class="status-row"><?= icon('mail') ?><span><?= e(t('Email Verification')) ?></span><?= badge($user['email_verified_at'] ? 'verified' : 'unverified') ?></div>
            <div class="status-row"><?= icon('shield-check') ?><span><?= e(t('Account Status')) ?></span><?= badge($user['status']) ?></div>
            <div class="status-row"><?= icon('user-check') ?><span><?= e(t('Verification')) ?></span><?= badge($user['verification_status']) ?></div>
            <div class="status-row"><?= icon('clock') ?><span><?= e(t('Last login')) ?></span><small><?= e($user['last_login_at'] ? fmt_datetime($user['last_login_at']) : '—') ?></small></div>
        </section>
        <section class="card">
            <h2 class="card-title"><?= e(t('Quick Actions')) ?></h2>
            <button class="quick-link" type="button" data-modal-open="#passwordModal"><span class="ql-icon"><?= icon('lock') ?></span><div><strong><?= e(t('Change Password')) ?></strong></div><?= icon('chevron-right') ?></button>
            <form class="quick-link as-form" method="post" action="<?= e(url('profile/two-factor')) ?>" data-ajax data-reload>
                <?= csrf_field() ?><input type="hidden" name="enable" value="<?= (int)$user['two_factor_enabled'] ? '0' : '1' ?>">
                <span class="ql-icon"><?= icon('shield') ?></span><div><strong><?= e(t('Two-factor authentication')) ?></strong><small><?= e((int)$user['two_factor_enabled'] || Settings::enabled('enable_2fa_all') ? t('Enabled – a code is emailed at login') : t('Add an email code at login')) ?></small></div>
                <?php if (!Settings::enabled('enable_2fa_all')): ?><button class="switch <?= (int)$user['two_factor_enabled'] ? 'on' : '' ?>" type="submit" aria-label="<?= e(t('Toggle two-factor authentication')) ?>"><span></span></button><?php else: ?><span class="badge badge-green"><?= e(t('Required')) ?></span><?php endif; ?>
            </form>
            <a class="quick-link" href="<?= e(url('lang/switch', ['to' => Lang::get() === 'bn' ? 'en' : 'bn'])) ?>"><span class="ql-icon"><?= icon('globe') ?></span><div><strong><?= e(t('Language')) ?></strong><small><?= Lang::get() === 'bn' ? 'বাংলা → English' : 'English → বাংলা' ?></small></div><?= icon('chevron-right') ?></a>
            <?php if ($user['role'] === 'farmer'): ?>
                <button class="quick-link danger" type="button" data-modal-open="#deleteModal"><span class="ql-icon"><?= icon('trash') ?></span><div><strong><?= e(t('Delete Account')) ?></strong></div><?= icon('chevron-right') ?></button>
            <?php endif; ?>
        </section>
    </aside>
</div>

<div class="modal" id="passwordModal" role="dialog" aria-modal="true" hidden>
    <form class="modal-card" method="post" action="<?= e(url('profile/password')) ?>" data-ajax data-reset data-close-modal novalidate>
        <div class="modal-head"><h3><?= e(t('Change Password')) ?></h3><button type="button" class="btn-icon" data-modal-close>×</button></div>
        <div class="modal-body">
            <?= csrf_field() ?>
            <div class="field"><label><?= e(t('Current Password')) ?></label><input type="password" name="current_password" autocomplete="current-password" required><?= field_error('current_password') ?></div>
            <div class="field"><label><?= e(t('New Password')) ?></label><input type="password" name="password" autocomplete="new-password" required data-strength><small class="hint" data-strength-label><?= e(t('At least 8 characters with a special character')) ?></small><?= field_error('password') ?></div>
            <div class="field"><label><?= e(t('Confirm New Password')) ?></label><input type="password" name="password_confirmation" autocomplete="new-password" required><?= field_error('password_confirmation') ?></div>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><?= e(t('Update Password')) ?></button></div>
    </form>
</div>

<?php if ($user['role'] === 'farmer'): ?>
<div class="modal" id="deleteModal" role="dialog" aria-modal="true" hidden>
    <form class="modal-card" method="post" action="<?= e(url('profile/delete')) ?>" data-ajax novalidate>
        <div class="modal-head"><h3 class="text-danger"><?= e(t('Delete Account')) ?></h3><button type="button" class="btn-icon" data-modal-close>×</button></div>
        <div class="modal-body">
            <?= csrf_field() ?>
            <div class="alert alert-danger"><?= icon('alert') ?><span><?= e(t('This permanently deactivates your account. You will not be able to log in again. Your rental history is kept for records.')) ?></span></div>
            <?php if ($hasActiveRentals): ?><div class="alert alert-warning"><?= icon('info') ?><span><?= e(t('Please complete or cancel your active rentals before deleting your account.')) ?></span></div><?php endif; ?>
            <div class="field"><label><?= e(t('Type DELETE to confirm')) ?></label><input name="confirmation" autocomplete="off" required><?= field_error('confirmation') ?></div>
            <div class="field"><label><?= e(t('Current Password')) ?></label><input type="password" name="password" autocomplete="current-password" required><?= field_error('password') ?></div>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-danger" <?= $hasActiveRentals ? 'disabled' : '' ?>><?= icon('trash') ?><?= e(t('Delete my account')) ?></button></div>
    </form>
</div>
<?php endif; ?>
