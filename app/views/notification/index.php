<div class="page-head">
    <div><h1><?= e(t('Notifications')) ?></h1></div>
    <form method="post" action="<?= e(url('notification/read-all')) ?>" data-ajax data-reload><?= csrf_field() ?><button class="btn btn-outline"><?= icon('check') ?><?= e(t('Mark all read')) ?></button></form>
</div>
<section class="card">
    <div class="chips">
        <a class="chip <?= $filter === 'all' ? 'active' : '' ?>" href="<?= e(url('notification/index')) ?>"><?= e(t('All')) ?></a>
        <a class="chip <?= $filter === 'unread' ? 'active' : '' ?>" href="<?= e(url('notification/index', ['filter' => 'unread'])) ?>"><?= e(t('Unread')) ?></a>
    </div>
    <div class="notif-list">
        <?php foreach ($rows as $n): $map = ['danger' => ['x-circle', 'red'], 'warning' => ['alert', 'amber'], 'success' => ['check-circle', 'green'], 'info' => ['info', 'blue']]; [$ni, $nc] = $map[$n['type']] ?? ['info', 'blue']; ?>
            <div class="notif <?= $n['is_read'] ? '' : 'unread' ?>" data-row="notif-<?= (int)$n['id'] ?>">
                <span class="li-icon <?= $nc ?>"><?= icon($ni) ?></span>
                <a class="notif-body" href="<?= e(url('notification/open', ['id' => $n['id']])) ?>"><strong><?= e(t($n['title'])) ?></strong><p><?= e($n['message']) ?></p><small><?= e(time_ago($n['created_at'])) ?> · <?= e(fmt_datetime($n['created_at'])) ?></small></a>
                <form method="post" action="<?= e(url('notification/delete')) ?>" data-ajax data-remove-row="notif-<?= (int)$n['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button class="btn-icon" aria-label="<?= e(t('Remove')) ?>"><?= icon('x', 'icon-xs') ?></button></form>
            </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?><?php $icon = 'bell'; $heading = t('No notifications'); $text = t("You're all caught up."); include __DIR__ . '/../partials/empty.php'; ?><?php endif; ?>
    </div>
    <?php include __DIR__ . '/../partials/pagination.php'; ?>
</section>
