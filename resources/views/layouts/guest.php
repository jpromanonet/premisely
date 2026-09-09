<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Premisely') . ' · Premisely') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" href="<?= e(asset('icons/logo-mark.svg')) ?>" type="image/svg+xml">
</head>
<body class="guest-body">
<main class="guest-main">
    <div class="guest-card">
        <div class="guest-brand-wrap">
            <img src="<?= e(asset('icons/logo-mark.svg')) ?>" alt="">
            <h1 class="guest-brand">Premisely</h1>
            <p class="guest-sub">Un lugar para todo lo importante.</p>
        </div>
        <?php require view_path('partials/flash.php'); ?>
        <?= $content ?? '' ?>
    </div>
</main>
</body>
</html>
