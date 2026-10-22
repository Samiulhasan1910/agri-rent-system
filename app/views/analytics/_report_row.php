<?php $typeColors = ['sales' => 'green', 'rental' => 'red', 'payment' => 'blue']; ?>
<tr>
    <td><strong><?= e(t(Report::TYPES[$r['type']])) ?></strong><small class="block muted"><?= e($r['report_no']) ?> · <?= e(fmt_datetime($r['created_at'])) ?></small></td>
    <td><span class="badge badge-<?= $typeColors[$r['type']] ?? 'gray' ?>"><?= e(t(ucfirst($r['type']))) ?></span></td>
    <td><?= e(fmt_date($r['date_from'])) ?> – <?= e(fmt_date($r['date_to'])) ?></td>
    <td><?= (int)$r['row_count'] ?></td>
    <td class="right"><?= money($r['total_amount']) ?></td>
    <td><?= e($r['generated_by_name'] ?? '—') ?></td>
    <td class="nowrap">
        <a class="btn btn-outline btn-xs" href="<?= e(url('analytics/download', ['id' => $r['id'], 'format' => 'pdf'])) ?>"><?= icon('download', 'icon-xs') ?>PDF</a>
        <a class="btn btn-light btn-xs" href="<?= e(url('analytics/download', ['id' => $r['id'], 'format' => 'csv'])) ?>"><?= icon('download', 'icon-xs') ?>CSV</a>
    </td>
</tr>
