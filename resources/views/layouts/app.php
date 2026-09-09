<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Premisely') . ' · Premisely') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650;9..144,700&family=IBM+Plex+Mono:wght@400;500&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
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
    <aside class="sidebar">
        <a class="brand" href="<?= e(url('/dashboard')) ?>">
            <img class="brand__mark" src="<?= e(asset('icons/logo-mark.svg')) ?>" alt="">
            <span class="brand__text">
                <span class="brand-wordmark">Premisely</span>
                <span class="brand__tag">Administrá propiedades, espacios y rutinas.</span>
            </span>
        </a>

        <nav class="nav" aria-label="Principal">
            <a class="<?= $currentPath === '/dashboard' || $currentPath === '/' ? 'active' : '' ?>" href="<?= e(url('/dashboard')) ?>"><span class="nav__icon">⌂</span> Resumen</a>
            <a class="<?= $isActive('/properties') && $pid === '' ? 'active' : '' ?>" href="<?= e(url('/properties')) ?>"><span class="nav__icon">▣</span> Propiedades</a>

            <?php if ($pid !== ''): ?>
                <div class="nav-section"><?= e($property['name'] ?? 'Propiedad') ?></div>
                <a class="<?= ($currentPath === '/properties/' . $pid || preg_match('#/properties/' . preg_quote($pid, '#') . '(/dashboard)?$#', $currentPath)) ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid)) ?>"><span class="nav__icon">◎</span> Resumen</a>
                <a class="<?= $isActive('/spaces') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/spaces')) ?>"><span class="nav__icon">▤</span> Espacios</a>
                <a class="<?= $isActive('/inventory') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/inventory')) ?>"><span class="nav__icon">▦</span> Inventario</a>
                <a class="<?= $isActive('/stock') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/stock')) ?>"><span class="nav__icon">◈</span> Stock</a>
                <a class="<?= $isActive('/shopping') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>"><span class="nav__icon">☰</span> Compras</a>
                <div class="nav-section">Operación</div>
                <a class="<?= $isActive('/tasks') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>"><span class="nav__icon">☑</span> Tareas</a>
                <a class="<?= $isActive('/routines') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/routines')) ?>"><span class="nav__icon">↻</span> Rutinas</a>
                <a class="<?= $isActive('/cleaning') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/cleaning')) ?>"><span class="nav__icon">✦</span> Limpieza</a>
                <a class="<?= $isActive('/maintenance') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>"><span class="nav__icon">⚒</span> Mantenimiento</a>
                <a class="<?= $isActive('/repairs') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/repairs')) ?>"><span class="nav__icon">🔧</span> Reparaciones</a>
                <a class="<?= $isActive('/services') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/services')) ?>"><span class="nav__icon">◍</span> Servicios</a>
                <a class="<?= $isActive('/expenses') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>"><span class="nav__icon">¤</span> Gastos</a>
                <a class="<?= $isActive('/documents') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/documents')) ?>"><span class="nav__icon">▤</span> Documentos</a>
                <a class="<?= $isActive('/members') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/members')) ?>"><span class="nav__icon">☺</span> Integrantes</a>
                <div class="nav-section">V1.5</div>
                <a class="<?= $isActive('/planning') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/planning')) ?>"><span class="nav__icon">▦</span> Plan semanal</a>
                <a class="<?= $isActive('/meals') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/meals')) ?>"><span class="nav__icon">◉</span> Comidas</a>
                <a class="<?= $isActive('/laundry') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/laundry')) ?>"><span class="nav__icon">≋</span> Lavandería</a>
                <a class="<?= $isActive('/clothing') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/clothing')) ?>"><span class="nav__icon">👕</span> Ropa</a>
                <a class="<?= $isActive('/providers') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/providers')) ?>"><span class="nav__icon">☎</span> Proveedores</a>
                <a class="<?= $isActive('/boxes') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/boxes')) ?>"><span class="nav__icon">❑</span> Cajas</a>
                <a class="<?= $isActive('/calendar') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/calendar')) ?>"><span class="nav__icon">☉</span> Calendario</a>
                <a class="<?= $isActive('/reports') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/reports')) ?>"><span class="nav__icon">▥</span> Reportes</a>
                <div class="nav-section">V2</div>
                <a class="<?= $isActive('/moves') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/moves')) ?>"><span class="nav__icon">⇢</span> Mudanzas</a>
                <a class="<?= $isActive('/automations') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/automations')) ?>"><span class="nav__icon">⚡</span> Automatizaciones</a>
                <a class="<?= $isActive('/consumption') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/consumption')) ?>"><span class="nav__icon">↘</span> Consumo</a>
                <a class="<?= $isActive('/suggestions') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/suggestions')) ?>"><span class="nav__icon">💡</span> Sugerencias</a>
                <a class="<?= $isActive('/importexport') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/importexport')) ?>"><span class="nav__icon">⇄</span> Import/Export</a>
                <a class="<?= $isActive('/api-tokens') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/api-tokens')) ?>"><span class="nav__icon">🔑</span> API</a>
                <a class="<?= $isActive('/integrations') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/integrations')) ?>"><span class="nav__icon">⛓</span> Integraciones</a>
            <?php endif; ?>

            <div class="nav-section">Cuenta</div>
            <a class="<?= $isActive('/notifications') ? 'active' : '' ?>" href="<?= e(url('/notifications')) ?>"><span class="nav__icon">🔔</span> Notificaciones</a>
            <a class="<?= $isActive('/settings') || $isActive('/profile') ? 'active' : '' ?>" href="<?= e(url('/settings/profile')) ?>"><span class="nav__icon">⚙</span> Configuración</a>
            <form method="post" action="<?= e(url('/logout')) ?>" style="margin-top:0.35rem">
                <?= csrf_field() ?>
                <button class="btn btn-secondary" type="submit" style="width:100%">Salir</button>
            </form>
        </nav>

        <div class="sidebar__footer">
            <img src="<?= e(asset('icons/plant-books.svg')) ?>" alt="">
            <p>Lugares más habitables, vidas más plenas.</p>
        </div>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <?php if ($pid !== ''): ?>
                <a class="property-switch" href="<?= e(url('/properties')) ?>">▣ <?= e($property['name'] ?? 'Propiedad') ?></a>
                <form class="search" method="get" action="<?= e(url('/properties/' . $pid . '/search')) ?>">
                    <label class="sr-only" for="q">Buscar</label>
                    <input id="q" name="q" placeholder="Buscar espacios, objetos, tareas, documentos...">
                </form>
            <?php else: ?>
                <a class="property-switch" href="<?= e(url('/properties')) ?>">▣ Tus propiedades</a>
                <div class="search"><input disabled placeholder="Elegí una propiedad para buscar..."></div>
            <?php endif; ?>

            <div class="topbar__actions">
                <a class="user-chip" href="<?= e(url('/settings/profile')) ?>">
                    <span class="avatar"><?= e($initials !== '' ? $initials : 'P') ?></span>
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

        <footer class="site-footer">
            <div><strong>Premisely</strong> — Administrá hoy. Disfrutá mañana.</div>
            <div>Hogares, espacios, planes y más vida. · V2</div>
        </footer>

        <?php if ($pid !== ''): ?>
        <nav class="mobile-nav" aria-label="Móvil">
            <a class="<?= !$isActive('/inventory') && !$isActive('/stock') && !$isActive('/tasks') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid)) ?>"><span>⌂</span>Resumen</a>
            <a class="<?= $isActive('/inventory') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/inventory')) ?>"><span>▦</span>Inventario</a>
            <a class="<?= $isActive('/stock') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/stock')) ?>"><span>◈</span>Stock</a>
            <a class="<?= $isActive('/tasks') ? 'active' : '' ?>" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>"><span>☑</span>Tareas</a>
            <a href="<?= e(url('/properties/' . $pid . '/search')) ?>"><span>···</span>Más</a>
        </nav>
        <?php endif; ?>
    </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
