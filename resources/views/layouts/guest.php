<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Premisely') . ' · Premisely') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="guest-body">
<main class="guest-main">
    <div class="guest-card">
        <h1 class="guest-brand">Premisely</h1>
        <?php require view_path('partials/flash.php'); ?>
        <?= $content ?? '' ?>
    </div>
</main>
</body>
</html>
