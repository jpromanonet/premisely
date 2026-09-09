<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Premisely') . ' · Premisely') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="app-body">
<header class="topbar">
    <div class="topbar__brand">
        <a href="<?= e(url('/dashboard')) ?>">Premisely</a>
    </div>
    <nav class="topbar__nav">
        <a href="<?= e(url('/dashboard')) ?>">Panel</a>
        <a href="<?= e(url('/properties')) ?>">Propiedades</a>
        <a href="<?= e(url('/profile')) ?>">Perfil</a>
        <form method="post" action="<?= e(url('/logout')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--ghost btn--sm">Salir</button>
        </form>
    </nav>
</header>

<?php if (!empty($property['public_id'])): ?>
<nav class="property-nav">
    <strong><?= e($property['name'] ?? '') ?></strong>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/dashboard')) ?>">Resumen</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/spaces')) ?>">Espacios</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/inventory')) ?>">Inventario</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/stock')) ?>">Stock</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/shopping')) ?>">Compras</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/tasks')) ?>">Tareas</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/routines')) ?>">Rutinas</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/cleaning')) ?>">Limpieza</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/maintenance')) ?>">Mantenimiento</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/repairs')) ?>">Reparaciones</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/services')) ?>">Servicios</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/expenses')) ?>">Gastos</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/documents')) ?>">Documentos</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/members')) ?>">Miembros</a>
    <a href="<?= e(url('/properties/' . $property['public_id'] . '/search')) ?>">Buscar</a>
</nav>
<?php endif; ?>

<main class="main">
    <?php require view_path('partials/flash.php'); ?>
    <?= $content ?? '' ?>
</main>

<footer class="footer">
    <span>Premisely</span>
</footer>
</body>
</html>
