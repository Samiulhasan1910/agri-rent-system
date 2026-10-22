<div class="page-head"><div><h1><?= e(is_role('admin') ? t('Account Verification') : t('Farmer Verification')) ?></h1><p class="muted"><?= e(t('Verify accounts so that only valid users can rent equipment.')) ?></p></div></div>
<div class="split-panel">
    <section class="card">
        <form class="toolbar" method="get" action="index.php" data-live data-target="#verifyList">
            <input type="hidden" name="r" value="verification/index">
            <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Search name, email or phone')) ?>"></div>
            <select name="status"><?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $l): ?><option value="<?= $k ?>"<?= selected($status, $k) ?>><?= e(t($l)) ?></option><?php endforeach; ?></select>
        </form>
        <div id="verifyList"><?php include __DIR__ . '/_list.php'; ?></div>
    </section>
    <aside class="card side-panel" id="verifyPanel" aria-live="polite">
        <?php if ($selected): $u = $selected; include __DIR__ . '/_panel.php'; else: ?><p class="muted center"><?= e(t('Select a user to see details.')) ?></p><?php endif; ?>
    </aside>
</div>
