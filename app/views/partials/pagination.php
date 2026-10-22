<?php
/** Expects $meta from paginate(). Links keep current filters; data-page lets live filters intercept. */
if (($meta['pages'] ?? 1) <= 1) {
    if (($meta['total'] ?? 0) > 0) {
        echo '<div class="pagination"><span class="muted">' . e(t('Showing :a to :b of :c entries', ['a' => 1, 'b' => $meta['total'], 'c' => $meta['total']])) . '</span></div>';
    }
    return;
}
$page = $meta['page'];
$pages = $meta['pages'];
$from = $meta['offset'] + 1;
$to = min($meta['offset'] + $meta['per'], $meta['total']);
$window = array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], static fn($p) => $p >= 1 && $p <= $pages));
sort($window);
?>
<div class="pagination">
    <span class="muted"><?= e(t('Showing :a to :b of :c entries', ['a' => $from, 'b' => $to, 'c' => $meta['total']])) ?></span>
    <nav class="pager" aria-label="<?= e(t('Pagination')) ?>">
        <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(page_url(max(1, $page - 1))) ?>" data-page="<?= max(1, $page - 1) ?>" aria-label="<?= e(t('Previous')) ?>"><?= icon('chevron-left', 'icon-xs') ?></a>
        <?php $prev = 0; foreach ($window as $p): ?>
            <?php if ($p - $prev > 1): ?><span class="gap">…</span><?php endif; ?>
            <a class="<?= $p === $page ? 'active' : '' ?>" href="<?= e(page_url($p)) ?>" data-page="<?= $p ?>"><?= $p ?></a>
        <?php $prev = $p; endforeach; ?>
        <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e(page_url(min($pages, $page + 1))) ?>" data-page="<?= min($pages, $page + 1) ?>" aria-label="<?= e(t('Next')) ?>"><?= icon('chevron-right', 'icon-xs') ?></a>
    </nav>
</div>
