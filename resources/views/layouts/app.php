<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Premisely') . ' · Premisely') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" href="<?= e(asset('icons/logo-mark.svg')) ?>" type="image/svg+xml">
</head>
<body>
<?php
$user = \Premisely\Core\Auth\Auth::user();
$property = $GLOBALS['current_property'] ?? ($property ?? null);
$pid = is_array($property) ? (string) ($property['public_id'] ?? '') : '';
$initials = '';
if (is_array($user) && !empty($user['name'])) {
    $parts = preg_split('/\s+/', trim((string) $user['name'])) ?: [];
    $initials = strtoupper(mb_substr($parts[0] ?? 'P', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
}
$currentPath = (string) ($_GET['r'] ?? '/');
$isActive = static function (string $needle) use ($currentPath): bool {
    return $needle !== '/' && str_contains($currentPath, $needle);
};
?>
<div class="app-shell">
    <aside class="sidebar" id="app-sidebar" data-sidebar-scroll>
        <a class="brand" href="<?= e(url('/dashboard')) ?>">
            <img class="brand__mark" src="<?= e(asset('icons/logo-mark.svg')) ?>" alt="">
            <span class="brand__text">
                <span class="brand-wordmark">Premisely</span>
            </span>
        </a>

        <nav class="nav" aria-label="Principal">
            <a class="<?= $currentPath === '/dashboard' || $currentPath === '/' ? 'active' : '' ?>" href="<?= e(url('/dashboard')) ?>"><span class="nav__icon">⌂</span> Resumen</a>
            <a class="<?= $isActive('/properties') && $pid === '' ? 'active' : '' ?>" href="<?= e(url('/properties')) ?>"><span class="nav__icon">▣</span> Propiedades</a>
            <a class="<?= $isActive('/notifications') ? 'active' : '' ?>" href="<?= e(url('/notifications')) ?>"><span class="nav__icon">🔔</span> Notificaciones</a>

            <?php if ($pid !== ''): ?>
                <div class="nav-section"><?= e($property['name'] ?? 'Propiedad') ?></div>
                <a class="<?= ($currentPath === '/properties/' . $pid || preg_match('#/properties/' . preg_quote($pid, '#') . '(/dashboard)?$#', $currentPath)) ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/dashboard')) ?>"><span class="nav__icon">◎</span> Panel</a>
                <a class="<?= $isActive('/spaces') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/spaces')) ?>"><span class="nav__icon">▤</span> Espacios</a>
                <a class="<?= $isActive('/members') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/members')) ?>"><span class="nav__icon">☺</span> Integrantes</a>

                <div class="nav-section">Pertenencias</div>
                <a class="<?= $isActive('/inventory') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/inventory')) ?>"><span class="nav__icon">▦</span> Inventario</a>
                <a class="<?= $isActive('/clothing') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/clothing')) ?>"><span class="nav__icon">👕</span> Ropa</a>

                <div class="nav-section">Consumibles</div>
                <a class="<?= $isActive('/stock') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/stock')) ?>"><span class="nav__icon">◈</span> Stock</a>
                <a class="<?= $isActive('/shopping') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>"><span class="nav__icon">☰</span> Compras</a>

                <div class="nav-section">Día a día</div>
                <a class="<?= $isActive('/calendar') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/calendar')) ?>"><span class="nav__icon">☉</span> Calendario</a>
                <a class="<?= $isActive('/routines') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/routines')) ?>"><span class="nav__icon">↻</span> Hábitos</a>

                <div class="nav-section">Cuidado</div>
                <a class="<?= $isActive('/maintenance') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>"><span class="nav__icon">⚒</span> Mantenimiento</a>
                <a class="<?= $isActive('/providers') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/providers')) ?>"><span class="nav__icon">☎</span> Proveedores</a>

                <div class="nav-section">Admin</div>
                <a class="<?= $isActive('/expenses') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>"><span class="nav__icon">¤</span> Gastos</a>
                <a class="<?= $isActive('/documents') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/documents')) ?>"><span class="nav__icon">▤</span> Documentos</a>
                <a class="<?= $isActive('/reports') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/reports')) ?>"><span class="nav__icon">▥</span> Reportes</a>

                <div class="nav-section">Cajas y mudanzas</div>
                <a class="<?= $isActive('/boxes') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/boxes')) ?>"><span class="nav__icon">❑</span> Cajas</a>
                <a class="<?= $isActive('/moves') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/moves')) ?>"><span class="nav__icon">⇢</span> Mudanzas</a>
            <?php endif; ?>

            <div class="nav-section">Cuenta</div>
            <a class="<?= $isActive('/settings') || $isActive('/profile') ? 'active' : '' ?>" href="<?= e(url('/settings/profile')) ?>"><span class="nav__icon">⚙</span> Configuración</a>
            <form method="post" action="<?= e(url('/logout')) ?>" style="margin-top:0.35rem">
                <?= csrf_field() ?>
                <button class="btn btn-secondary" type="submit" style="width:100%">Salir</button>
            </form>
        </nav>
    </aside>
    <button type="button" class="nav-backdrop" data-nav-close aria-label="Cerrar menú"></button>

    <div class="workspace">
        <header class="topbar">
            <button type="button" class="nav-toggle" aria-controls="app-sidebar" aria-expanded="false" aria-label="Abrir menú">
                <span></span><span></span><span></span>
            </button>
            <?php if ($pid !== ''): ?>
                <a class="property-switch" href="<?= e(url('/properties')) ?>">▣ <?= e($property['name'] ?? 'Propiedad') ?></a>
                <form class="search" method="get" action="<?= e(form_target()) ?>">
                    <input type="hidden" name="r" value="/properties/<?= e($pid) ?>/search">
                    <label class="sr-only" for="q">Buscar</label>
                    <input id="q" name="q" placeholder="Buscar espacios, objetos, tareas, documentos..." required>
                </form>
            <?php else: ?>
                <a class="property-switch" href="<?= e(url('/properties')) ?>">▣ Tus propiedades</a>
                <div class="search"><input disabled placeholder="Elegí una propiedad para buscar..."></div>
            <?php endif; ?>

            <div class="topbar__actions">
                <a class="user-chip" href="<?= e(url('/settings/profile')) ?>">
                    <?php if (!empty($user['avatar_path']) && !empty($user['public_id'])): ?>
                        <span class="avatar avatar--img" style="background-image:url('<?= e(url('/avatars/' . $user['public_id'])) ?>')" role="img" aria-label=""></span>
                    <?php else: ?>
                        <span class="avatar"><?= e($initials !== '' ? $initials : 'P') ?></span>
                    <?php endif; ?>
                    <span>
                        <?= e($user['name'] ?? 'Cuenta') ?>
                        <small>Perfil</small>
                    </span>
                </a>
            </div>
        </header>

        <main class="main">
            <?php require view_path('partials/flash.php'); ?>
            <?= $content ?? '' ?>
        </main>

    </div>
</div>
<button type="button" class="to-top" aria-label="Volver arriba">
    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 14l6-6 6 6"/></svg>
</button>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
