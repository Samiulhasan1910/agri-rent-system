<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database setup required | AgriRent</title>
    <style>
        body{margin:0;font-family:Poppins,Segoe UI,Arial,sans-serif;background:#f1f7f2;color:#1d2b22;display:grid;place-items:center;min-height:100vh;padding:20px}
        .card{max-width:640px;background:#fff;border:1px solid #dfeae2;border-radius:16px;padding:32px;box-shadow:0 12px 32px rgba(20,83,45,.08)}
        h1{margin:0 0 8px;color:#14532d;font-size:24px} ol{line-height:1.9;padding-left:20px} code{background:#eef6f0;padding:2px 6px;border-radius:5px}
        .err{margin-top:18px;font-size:12px;color:#9a3412;background:#fff7ed;border:1px solid #fed7aa;padding:10px;border-radius:8px;word-break:break-word}
        .brand{display:flex;gap:10px;align-items:center;font-weight:700;color:#16803c;font-size:20px;margin-bottom:18px}
    </style>
</head>
<body>
<div class="card">
    <div class="brand"><img src="assets/img/logo.svg" width="34" height="34" alt="">AgriRent</div>
    <h1>Database setup required</h1>
    <p>The AgriRent database is missing or uses an older structure. Please (re)import the SQL file:</p>
    <ol>
        <li>Start <strong>Apache</strong> and <strong>MySQL</strong> in the XAMPP Control Panel.</li>
        <li>Open <a href="/phpmyadmin/" target="_blank">phpMyAdmin</a> → <strong>Import</strong>.</li>
        <li>Choose <code>database/agriproject.sql</code> from this project and click <strong>Import</strong>. It creates/resets the <code>agriproject</code> database.</li>
        <li>Reload this page and log in with <code>admin@agrirent.test</code> / <code>Admin@123</code>.</li>
    </ol>
    <p>Different MySQL credentials? Edit <code>config/config.php</code>.</p>
    <?php if (config('app.debug') && !empty($error)): ?><div class="err"><?= htmlspecialchars((string)$error) ?></div><?php endif; ?>
</div>
</body>
</html>
