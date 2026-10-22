<?php $staff = $user['role'] !== 'farmer'; ?>
<?php if (!$staff): ?><nav class="crumbs"><a href="<?= e(url('complaint/index')) ?>"><?= e(t('Complaints')) ?></a><?= icon('chevron-right', 'icon-xs') ?><span>#<?= e($c['ticket_no']) ?></span></nav><?php endif; ?>
<section class="card success-card <?= $isNew ? '' : 'plain' ?>">
    <?php if ($isNew): ?>
        <span class="success-icon"><?= icon('check-circle') ?></span>
        <h1><?= e(t('Complaint Submitted Successfully!')) ?></h1>
        <p class="muted"><?= e(t('Your complaint has been submitted.')) ?></p>
    <?php else: ?>
        <h1><?= e($c['subject']) ?></h1>
    <?php endif; ?>
    <div class="sub-card receipt">
        <dl class="kv">
            <div><dt><?= icon('tag', 'icon-xs') ?><?= e(t('Ticket ID')) ?></dt><dd><strong>#<?= e($c['ticket_no']) ?></strong></dd></div>
            <div><dt><?= icon('clock', 'icon-xs') ?><?= e(t('Status')) ?></dt><dd data-complaint-status><?= badge($c['status']) ?></dd></div>
            <div><dt><?= icon('grid', 'icon-xs') ?><?= e(t('Category')) ?></dt><dd><?= e(t($c['category'])) ?></dd></div>
            <div><dt><?= icon('alert', 'icon-xs') ?><?= e(t('Priority')) ?></dt><dd><?= badge($c['priority']) ?></dd></div>
            <div><dt><?= icon('calendar', 'icon-xs') ?><?= e(t('Submitted On')) ?></dt><dd><?= e(fmt_datetime($c['created_at'])) ?></dd></div>
            <?php if ($c['booking_code']): ?><div><dt><?= icon('calendar-check', 'icon-xs') ?><?= e(t('Booking')) ?></dt><dd>#<?= e($c['booking_code']) ?></dd></div><?php endif; ?>
            <?php if ($staff): ?><div><dt><?= icon('user', 'icon-xs') ?><?= e(t('Submitted by')) ?></dt><dd><?= e($c['user_name']) ?> · <?= e($c['user_phone']) ?></dd></div><?php endif; ?>
            <div><dt><?= icon('file-text', 'icon-xs') ?><?= e(t('Description')) ?></dt><dd><?= nl2br(e($c['description'])) ?></dd></div>
            <?php if ($c['attachment']): ?><div><dt><?= icon('image', 'icon-xs') ?><?= e(t('Attachment')) ?></dt><dd><a href="<?= e($c['attachment']) ?>" target="_blank"><img class="attach" src="<?= e($c['attachment']) ?>" alt=""></a></dd></div><?php endif; ?>
        </dl>
    </div>
    <?php if ($c['admin_response']): ?>
        <div class="alert alert-success"><?= icon('message') ?><span><strong><?= e(t('Response')) ?><?= $c['handler_name'] ? ' (' . e($c['handler_name']) . ')' : '' ?>:</strong> <?= e($c['admin_response']) ?></span></div>
    <?php endif; ?>
    <?php if ($staff): ?>
        <form class="stack left" method="post" action="<?= e(url('complaint/update')) ?>" data-ajax data-reload>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <div class="form-grid">
                <div class="field"><label><?= e(t('Status')) ?></label><select name="status"><?php foreach (Complaint::STATUSES as $s): ?><option value="<?= $s ?>"<?= selected($c['status'], $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label><?= e(t('Response to user')) ?></label><input name="response" value="<?= e($c['admin_response'] ?? '') ?>"></div>
            </div>
            <button class="btn btn-primary" type="submit"><?= e(t('Update Status')) ?></button>
        </form>
    <?php else: ?>
        <div class="stack narrow-stack">
            <a class="btn btn-outline btn-block" href="<?= e(url('chat/index')) ?>"><?= icon('message') ?><?= e(t('Chat with Employee')) ?></a>
            <a class="btn btn-outline btn-block" href="<?= e(url('dashboard/index')) ?>"><?= icon('home') ?><?= e(t('Back to Dashboard')) ?></a>
        </div>
    <?php endif; ?>
</section>
