<!doctype html>
<html lang="<?= Lang::get() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Document') ?> | <?= e(setting('platform_name', 'AgriRent')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Hind+Siliguri:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
</head>
<body class="print-page">
<div class="print-toolbar no-print">
    <button class="btn btn-primary btn-sm" type="button" onclick="window.print()"><?= icon('printer') ?><?= e(t('Print / Save as PDF')) ?></button>
    <a class="btn btn-outline btn-sm" href="javascript:history.back()"><?= icon('arrow-left') ?><?= e(t('Back')) ?></a>
</div>
<?= $content ?>
</body>
</html>
