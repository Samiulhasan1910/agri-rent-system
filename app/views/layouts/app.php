<?php
$me = Auth::user();
$current = Router::current();
$items = nav_items($me['role']);
$aliases = [
    'equipment/show' => 'equipment/index', 'equipment/edit' => 'equipment/manage', 'booking/confirm' => 'equipment/index',
    'booking/show' => $me['role'] === 'farmer' ? 'booking/index' : 'rental/index', 'booking/cancel' => 'booking/index',
    'booking/cancelled' => 'booking/index', 'booking/extend' => 'booking/index', 'payment/checkout' => 'payment/history',
    'payment/pay' => 'payment/history', 'payment/complete' => 'payment/history', 'complaint/show' => 'complaint/index',
    'notification/index' => '', 'mailbox/index' => '',
];
$routes = array_column($items, 0);
$active = in_array($current, $routes, true) ? $current : ($aliases[$current] ?? '');
if ($active === '' && !array_key_exists($current, $aliases)) {
    foreach ($routes as $r) {
        if (explode('/', $r)[0] === explode('/', $current)[0]) {
            $active = $r;
            break;
        }
    }
}
$unread = (new Notification())->unreadCount((int)$me['id']);
$unreadMessages = (new Message())->unreadCount((int)$me['id']);
?>
<!doctype html>
<html lang="<?= Lang::get() ?>" data-lang="<?= Lang::get() ?>">
<head><?php include __DIR__ . '/_head.php'; ?></head>
<body class="app role-<?= e($me['role']) ?>" data-route="<?= e($current) ?>">
<a class="skip-link" href="#main"><?= e(t('Skip to content')) ?></a>
<header class="topbar">
    <button class="btn-icon sidebar-toggle" type="button" data-toggle-class="sidebar-open" data-target="body" aria-label="<?= e(t('Menu')) ?>"><?= icon('menu') ?></button>
    <a class="brand" href="<?= e(url('dashboard/index')) ?>"><?= icon('leaf') ?><span><?= e(setting('platform_name', 'AgriRent')) ?></span></a>
    <div class="topbar-title"><?= $me['role'] !== 'farmer' ? e(t(ucfirst($me['role']) . ' Dashboard')) : '' ?></div>
    <div class="topbar-actions">
        <div class="net-status" id="netStatus" title="<?= e(t('Connection & offline sync status')) ?>">
            <span class="net-dot"></span><span data-net-label><?= e(t('Online')) ?></span>
            <button class="btn-link" type="button" data-sync-now hidden><?= icon('refresh', 'icon-xs') ?><span data-sync-label><?= e(t('Sync')) ?></span></button>
        </div>
        <?php include __DIR__ . '/_lang.php'; ?>
        <div class="dropdown notif-dropdown">
            <button class="btn-icon bell" type="button" data-dropdown data-notifications aria-label="<?= e(t('Notifications')) ?>">
                <?= icon('bell') ?><b class="count" data-notif-count <?= $unread ? '' : 'hidden' ?>><?= $unread ?></b>
            </button>
            <div class="dropdown-menu dropdown-wide">
                <div class="dropdown-head"><strong><?= e(t('Notifications')) ?></strong>
                    <form method="post" action="<?= e(url('notification/read-all')) ?>" data-ajax data-on-success="notifications-read"><?= csrf_field() ?><button class="btn-link" type="submit"><?= e(t('Mark all read')) ?></button></form>
                </div>
                <div data-notif-list><div class="dropdown-empty"><?= e(t('Loading…')) ?></div></div>
                <a class="dropdown-foot" href="<?= e(url('notification/index')) ?>"><?= e(t('View all notifications')) ?></a>
            </div>
        </div>
        <div class="dropdown">
            <button class="user-chip" type="button" data-dropdown aria-label="<?= e(t('Account menu')) ?>"><?= avatar($me, 'sm') ?></button>
            <div class="dropdown-menu">
                <div class="dropdown-head"><strong><?= e($me['name']) ?></strong><small><?= e($me['email']) ?></small></div>
                <a href="<?= e(url('profile/index')) ?>"><?= icon('user') ?><?= e(t('Profile')) ?></a>
                <a href="<?= e(url('chat/index')) ?>"><?= icon('message') ?><?= e(t('Messages')) ?><?php if ($unreadMessages): ?> <b class="pill" data-msg-count><?= $unreadMessages ?></b><?php endif; ?></a>
                <a href="<?= e(url('notification/index')) ?>"><?= icon('bell') ?><?= e(t('Notifications')) ?></a>
                <form method="post" action="<?= e(url('auth/logout')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout') ?><?= e(t('Log Out')) ?></button></form>
            </div>
        </div>
    </div>
</header>

<aside class="sidebar" aria-label="<?= e(t('Sidebar')) ?>">
    <div class="sidebar-profile">
        <?= avatar($me, 'xl') ?>
        <strong><?= e($me['name']) ?></strong>
        <span><?= e($me['role'] === 'admin' ? t('System Administrator') : role_label($me['role'])) ?></span>
    </div>
    <nav class="side-nav">
        <?php foreach ($items as [$route, $label, $ico]): ?>
            <a class="nav-link <?= $route === $active ? 'active' : '' ?>" href="<?= e(url($route)) ?>"><?= icon($ico) ?><span><?= e(t($label)) ?></span>
                <?php if ($route === 'chat/index' && $unreadMessages): ?><b class="pill" data-msg-count><?= $unreadMessages ?></b><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <form class="side-logout" method="post" action="<?= e(url('auth/logout')) ?>"><?= csrf_field() ?>
        <button class="nav-link" type="submit"><?= icon('logout') ?><span><?= e(t('Log Out')) ?></span></button>
    </form>
</aside>
<div class="sidebar-backdrop" data-toggle-class="sidebar-open" data-target="body"></div>

<main id="main" class="main">
    <?php if ($me['role'] === 'farmer' && $me['verification_status'] !== 'approved'): ?>
        <div class="alert alert-<?= $me['verification_status'] === 'rejected' ? 'danger' : 'warning' ?> verify-banner">
            <?= icon($me['verification_status'] === 'rejected' ? 'x-circle' : 'shield') ?>
            <div>
                <strong><?= e($me['verification_status'] === 'rejected' ? t('Account verification rejected') : t('Account verification pending')) ?></strong>
                <p><?= e($me['verification_status'] === 'rejected'
                        ? t('Reason: :note Please upload a valid document to try again.', ['note' => $me['verification_note'] ?: '-'])
                        : t('You can browse equipment now. Booking becomes available after an employee verifies your account.')) ?></p>
            </div>
            <a class="btn btn-sm btn-outline" href="<?= e(url('profile/index')) ?>#verification"><?= e(empty($me['verification_document']) || $me['verification_status'] === 'rejected' ? t('Upload document') : t('View status')) ?></a>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
<?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/modal.php'; ?>
</body>
</html>
