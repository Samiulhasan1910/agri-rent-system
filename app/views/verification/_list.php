<div class="list">
    <?php foreach ($rows as $u): ?>
        <button type="button" class="list-item selectable-item" data-row="user-<?= (int)$u['id'] ?>" data-panel-url="<?= e(url('verification/show', ['id' => $u['id']])) ?>" data-panel-target="#verifyPanel">
            <?= avatar($u, 'sm') ?>
            <div><strong><?= e($u['name']) ?></strong><small><?= e(role_label($u['role'])) ?> · <?= e($u['address']) ?> · <?= e(time_ago($u['created_at'])) ?></small></div>
            <span><?= badge($u['verification_status']) ?><?= $u['verification_document'] ? ' ' . icon('paperclip', 'icon-xs') : '' ?></span>
        </button>
    <?php endforeach; ?>
    <?php if (!$rows): ?><?php $icon = 'user-check'; $heading = t('No verification requests'); $text = t('New registrations waiting for verification will appear here.'); include __DIR__ . '/../partials/empty.php'; ?><?php endif; ?>
</div>
