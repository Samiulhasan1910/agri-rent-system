<div class="contact-list">
    <?php foreach ($contacts as $c): ?>
        <a class="contact <?= $other && (int)$other['id'] === (int)$c['id'] ? 'active' : '' ?>" href="<?= e(url('chat/index', ['with' => $c['id']])) ?>">
            <?= avatar($c, 'sm') ?>
            <div><strong><?= e($c['name']) ?></strong><small><?= e($c['last_message'] ? str_limit($c['last_message'], 34) : role_label($c['role'])) ?></small></div>
            <span class="contact-meta"><?php if ($c['last_at']): ?><small><?= e(time_ago($c['last_at'])) ?></small><?php endif; ?><?php if ((int)$c['unread']): ?><b class="pill"><?= (int)$c['unread'] ?></b><?php endif; ?></span>
        </a>
    <?php endforeach; ?>
    <?php if (!$contacts): ?><p class="muted center small"><?= e(t('No conversations yet.')) ?></p><?php endif; ?>
</div>
