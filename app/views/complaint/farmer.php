<div class="page-head"><div><h1><?= e(t('Complaint')) ?></h1><p class="muted"><?= e(t('Submit your equipment or service issue.')) ?></p></div></div>
<div class="grid-2-1">
    <section class="card">
        <form method="post" action="<?= e(url('complaint/store')) ?>" enctype="multipart/form-data" data-ajax data-offline="complaint" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field span-2"><label for="c-sub"><?= e(t('Subject')) ?></label><input id="c-sub" name="subject" maxlength="180" value="<?= e(old('subject')) ?>" placeholder="<?= e(t('Enter complaint subject')) ?>" required><?= field_error('subject') ?></div>
                <div class="field"><label for="c-cat"><?= e(t('Complaint Category')) ?></label><select id="c-cat" name="category" required><option value=""><?= e(t('Select Category')) ?></option><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"<?= selected(old('category'), $c) ?>><?= e(t($c)) ?></option><?php endforeach; ?></select><?= field_error('category') ?></div>
                <div class="field"><label for="c-pri"><?= e(t('Priority')) ?></label><select id="c-pri" name="priority"><option value="medium"><?= e(t('Select Priority')) ?></option><option value="low"><?= e(t('Low')) ?></option><option value="medium"><?= e(t('Medium')) ?></option><option value="high"><?= e(t('High')) ?></option></select></div>
                <div class="field span-2"><label for="c-bk"><?= e(t('Related booking (optional)')) ?></label><select id="c-bk" name="booking_id"><option value=""><?= e(t('None')) ?></option><?php foreach ($bookings as $b): ?><option value="<?= (int)$b['id'] ?>"<?= selected($preBooking, $b['id']) ?>><?= e($b['booking_code']) ?> · <?= e($b['equipment_name']) ?></option><?php endforeach; ?></select></div>
                <div class="field span-2"><label for="c-desc"><?= e(t('Description')) ?></label><textarea id="c-desc" name="description" rows="5" maxlength="3000" placeholder="<?= e(t('Describe your issue in detail...')) ?>" required><?= e(old('description')) ?></textarea><?= field_error('description') ?></div>
                <div class="field"><label><?= e(t('Upload Image (Optional)')) ?></label><label class="file-drop small" for="c-img"><?= icon('image') ?><span data-file-label><?= e(t('Click to upload image')) ?></span></label><input class="visually-hidden" id="c-img" type="file" name="attachment" accept="image/*" data-file-input><?= field_error('attachment') ?></div>
                <div class="field form-end"><button class="btn btn-primary btn-block" type="submit"><?= icon('send') ?><?= e(t('Submit Complaint')) ?></button></div>
            </div>
        </form>
    </section>
    <aside class="card help-card tall">
        <span class="success-icon sm"><?= icon('headset') ?></span>
        <h3><?= e(t('We are here to help!')) ?></h3>
        <p class="muted"><?= e(t('Our team will respond as soon as possible.')) ?></p>
        <dl class="kv">
            <div><dt><?= icon('mail', 'icon-xs') ?><?= e(t('Email')) ?></dt><dd><?= e(setting('support_email')) ?></dd></div>
            <div><dt><?= icon('phone', 'icon-xs') ?><?= e(t('Hotline')) ?></dt><dd><?= e(setting('support_phone')) ?></dd></div>
            <div><dt><?= icon('clock', 'icon-xs') ?><?= e(t('Average Response Time')) ?></dt><dd><?= e(t('Within 24 hours')) ?></dd></div>
        </dl>
        <a class="btn btn-outline btn-block" href="<?= e(url('chat/index')) ?>"><?= icon('message') ?><?= e(t('Chat with Employee')) ?></a>
    </aside>
</div>
<section class="card">
    <h2 class="card-title"><?= e(t('Previous Complaints')) ?></h2>
    <div class="table-wrap"><table class="table">
        <thead><tr><th><?= e(t('Complaint ID')) ?></th><th><?= e(t('Subject')) ?></th><th><?= e(t('Category')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Priority')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr><td><strong>#<?= e($c['ticket_no']) ?></strong></td><td><?= e($c['subject']) ?></td><td><?= e(t($c['category'])) ?></td><td><?= e(fmt_date($c['created_at'])) ?></td><td><?= badge($c['priority']) ?></td><td><?= badge($c['status']) ?></td>
                <td><a class="btn btn-light btn-xs" href="<?= e(url('complaint/show', ['id' => $c['id']])) ?>"><?= e(t('View')) ?></a></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="center muted"><?= e(t('You have not submitted any complaints.')) ?></td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
