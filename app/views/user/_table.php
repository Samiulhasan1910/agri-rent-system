<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('User')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Phone')) ?></th><th><?= e(t('Account')) ?></th><th><?= e(t('Verification')) ?></th><th><?= e(t('Last login')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
            <tr>
                <td><div class="cell-media"><?= avatar($u, 'sm') ?><div><strong><?= e($u['name']) ?></strong><small class="block muted"><?= e($u['email']) ?></small></div></div></td>
                <td><?= e(role_label($u['role'])) ?><?= $u['employee_code'] ? '<small class="block muted">' . e($u['employee_code']) . '</small>' : '' ?></td>
                <td><?= e($u['phone']) ?></td>
                <td><?= badge($u['status'], $u['status'] === 'inactive' ? t('Email Unverified') : null) ?><?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?><small class="block text-danger"><?= e(t('Locked')) ?></small><?php endif; ?></td>
                <td><?= badge($u['verification_status']) ?></td>
                <td><small><?= e($u['last_login_at'] ? fmt_datetime($u['last_login_at']) : '—') ?></small></td>
                <td class="nowrap">
                    <?php if ($u['role'] !== 'admin' && $u['status'] !== 'deleted'): ?>
                        <form class="inline" method="post" action="<?= e(url('user/status')) ?>" data-ajax data-reload data-confirm="<?= e($u['status'] === 'active' ? t('Deactivate this account?') : t('Activate this account?')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>"><button class="btn <?= $u['status'] === 'active' ? 'btn-danger-soft' : 'btn-primary' ?> btn-xs"><?= e($u['status'] === 'active' ? t('Deactivate') : t('Activate')) ?></button></form>
                    <?php endif; ?>
                    <?php if ($u['status'] !== 'deleted'): ?>
                        <form class="inline" method="post" action="<?= e(url('user/reset-password')) ?>" data-ajax data-confirm="<?= e(t('Send a password reset link to :email?', ['email' => $u['email']])) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-light btn-xs"><?= icon('key', 'icon-xs') ?><?= e(t('Reset password')) ?></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="center muted"><?= e(t('No users found.')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
