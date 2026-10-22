<div class="table-wrap">
    <table class="table">
        <thead><tr><th><?= e(t('Ticket ID')) ?></th><th><?= e(t('Category')) ?></th><th><?= e(t('Date')) ?></th><th><?= e(t('Subject')) ?></th><th><?= e(t('User')) ?></th><th><?= e(t('Priority')) ?></th><th><?= e(t('Current Status')) ?></th><th><?= e(t('Action')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr data-row="cmp-<?= (int)$c['id'] ?>">
                <td><strong><?= e($c['ticket_no']) ?></strong></td>
                <td><?= e(t($c['category'])) ?></td>
                <td><?= e(fmt_date($c['created_at'])) ?></td>
                <td><?= e(str_limit($c['subject'], 40)) ?><a class="block small" href="<?= e(url('complaint/show', ['id' => $c['id']])) ?>">(<?= e(t('View Complaint')) ?>)</a></td>
                <td><?= e($c['user_name']) ?><small class="block muted"><?= e(role_label($c['user_role'])) ?></small></td>
                <td><?= badge($c['priority']) ?></td>
                <td data-status-cell><?= badge($c['status']) ?></td>
                <td><button class="btn btn-light btn-xs" type="button" data-modal-open="#complaintModal" data-complaint-edit="<?= e(url('complaint/show', ['id' => $c['id']])) ?>"><?= e(t('Update Status')) ?></button></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="center muted"><?= e(t('No complaints found.')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../partials/pagination.php'; ?>
