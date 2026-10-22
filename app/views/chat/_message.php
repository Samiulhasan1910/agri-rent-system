<div class="bubble <?= (int)$m['sender_id'] === $me ? 'mine' : '' ?>" data-msg="<?= (int)$m['id'] ?>">
    <p><?= nl2br(e($m['message'])) ?></p>
    <small><?= e(fmt_date($m['created_at'], 'd M, h:i A')) ?><?= (int)$m['sender_id'] === $me ? ' ' . ((int)$m['is_read'] ? '✓✓' : '✓') : '' ?></small>
</div>
