<?php foreach ($rows as $n): $map = ['danger' => ['x-circle', 'red'], 'warning' => ['alert', 'amber'], 'success' => ['check-circle', 'green'], 'info' => ['info', 'blue']]; [$ni, $nc] = $map[$n['type']] ?? ['info', 'blue']; ?>
    <a class="dropdown-notif <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= e(url('notification/open', ['id' => $n['id']])) ?>">
        <span class="li-icon <?= $nc ?>"><?= icon($ni) ?></span>
        <span><strong><?= e(t($n['title'])) ?></strong><small><?= e(str_limit($n['message'], 80)) ?></small><em><?= e(time_ago($n['created_at'])) ?></em></span>
    </a>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="dropdown-empty"><?= e(t('No notifications')) ?></div><?php endif; ?>
