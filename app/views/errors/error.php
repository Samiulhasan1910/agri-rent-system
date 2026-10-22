<?php
$icons = [403 => 'lock', 404 => 'search', 405 => 'info', 419 => 'refresh', 422 => 'alert'];
$titles = [403 => t('Access denied'), 404 => t('Page not found'), 405 => t('Method not allowed'), 419 => t('Page expired'), 422 => t('Request could not be processed')];
?>
<section class="error-page">
    <div class="error-card">
        <span class="error-code"><?= (int)$status ?></span>
        <span class="auth-icon <?= $status >= 500 ? 'auth-icon-danger' : '' ?>"><?= icon($icons[$status] ?? 'alert') ?></span>
        <h1><?= e($titles[$status] ?? t('Something went wrong')) ?></h1>
        <p class="muted"><?= e($message) ?></p>
        <div class="row-actions center">
            <a class="btn btn-outline" href="javascript:history.back()"><?= icon('arrow-left') ?><?= e(t('Go Back')) ?></a>
            <a class="btn btn-primary" href="<?= e(url(Auth::check() ? 'dashboard/index' : 'home/index')) ?>"><?= icon('home') ?><?= e(t('Home')) ?></a>
        </div>
        <?php if (!empty($trace)): ?><pre class="trace"><?= e($trace) ?></pre><?php endif; ?>
    </div>
</section>
