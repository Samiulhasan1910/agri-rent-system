<div class="panel-head"><h2><?= e(role_label($u['role'])) ?> <?= e(t('Details')) ?></h2><?= badge($u['verification_status']) ?></div>
<div class="profile-mini"><?= avatar($u, 'lg') ?><div><strong><?= e($u['name']) ?></strong><small><?= e($u['email']) ?></small></div></div>
<dl class="kv">
    <div><dt><?= icon('phone', 'icon-xs') ?><?= e(t('Phone')) ?></dt><dd><?= e($u['phone']) ?></dd></div>
    <div><dt><?= icon('map-pin', 'icon-xs') ?><?= e(t('Address')) ?></dt><dd><?= e($u['address']) ?></dd></div>
    <div><dt><?= icon('mail', 'icon-xs') ?><?= e(t('Email verification')) ?></dt><dd><?= badge($u['email_verified_at'] ? 'verified' : 'unverified') ?></dd></div>
    <div><dt><?= icon('calendar', 'icon-xs') ?><?= e(t('Registered')) ?></dt><dd><?= e(fmt_datetime($u['created_at'])) ?></dd></div>
    <?php if ($u['verification_note']): ?><div><dt><?= e(t('Note')) ?></dt><dd><?= e($u['verification_note']) ?></dd></div><?php endif; ?>
</dl>
<h3 class="sub-title"><?= e(t('Submitted document')) ?></h3>
<?php if ($u['verification_document']): $docUrl = url('verification/document', ['id' => $u['id']]); $isPdf = str_ends_with((string)$u['verification_document'], '.pdf'); ?>
    <div class="doc-preview">
        <?php if ($isPdf): ?><object data="<?= e($docUrl) ?>" type="application/pdf"><a href="<?= e($docUrl) ?>" target="_blank"><?= e(t('Open document')) ?></a></object><?php else: ?><img src="<?= e($docUrl) ?>" alt="<?= e(t('Verification document')) ?>" onerror="this.replaceWith(document.createTextNode('<?= e(t('Unable to load farmer details.')) ?>'))"><?php endif; ?>
    </div>
    <a class="btn btn-light btn-sm" href="<?= e($docUrl) ?>" target="_blank"><?= icon('external', 'icon-xs') ?><?= e(t('Open document')) ?></a>
<?php else: ?>
    <div class="alert alert-warning"><?= icon('alert') ?><span><?= e(t('No document uploaded yet.')) ?></span></div>
<?php endif; ?>
<?php if ($u['verification_status'] !== 'approved'): ?>
    <form method="post" action="<?= e(url('verification/decide')) ?>" data-ajax data-reload class="stack">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
        <textarea name="note" rows="2" placeholder="<?= e(t('Reason (required when rejecting)')) ?>"></textarea><?= field_error('note') ?>
        <div class="row-actions">
            <button class="btn btn-primary" type="submit" name="decision" value="approved"><?= icon('check') ?><?= e(t('Approve')) ?></button>
            <button class="btn btn-danger" type="submit" name="decision" value="rejected"><?= icon('x') ?><?= e(t('Reject')) ?></button>
        </div>
    </form>
<?php else: ?>
    <p class="muted small"><?= icon('check-circle', 'icon-xs') ?> <?= e(t('Verified on :date', ['date' => fmt_date($u['verified_at'])])) ?></p>
<?php endif; ?>
