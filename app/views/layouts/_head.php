<?php
/** Shared <head> markup. Expects $title. */
$lang = Lang::get();
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#16803c">
<meta name="description" content="<?= e(t('Rent tractors, harvesters, pumps and farming tools from trusted owners.')) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? '') !== '' ? $title . ' | ' : '') ?><?= e(setting('platform_name', 'AgriRent')) ?></title>
<link rel="icon" href="assets/img/logo.svg" type="image/svg+xml">
<link rel="manifest" href="manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
<script>
window.APP = <?= json_encode([
    'lang' => $lang,
    'csrf' => csrf_token(),
    'user' => Auth::user() ? ['id' => (int)Auth::user()['id'], 'role' => Auth::user()['role'], 'verified' => Auth::user()['verification_status'] === 'approved'] : null,
    'currency' => '৳',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php if ($lang === 'bn'): ?><script src="<?= e(url('lang/js')) ?>&v=<?= is_file(APP_PATH . '/lang/bn.php') ? filemtime(APP_PATH . '/lang/bn.php') : 1 ?>"></script><?php endif; ?>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/charts.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/offline.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/pages.js')) ?>" defer></script>
