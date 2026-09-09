<div class="page-hero">
    <div class="page-hero__copy">
        <h1>Resumen</h1>
        <p>Tu mundo mejor organizado. Acá ves el estado general de todas tus propiedades.</p>
    </div>
    <div class="page-hero__art">
        <img src="<?= e(asset('icons/hero-window.svg')) ?>" alt="">
        <div class="motto-chip">Buenas rutinas. Hogares más libres.</div>
    </div>
</div>

<div class="card-grid">
    <div class="stat"><span>Propiedades</span><strong><?= (int) $stats['properties'] ?></strong></div>
    <div class="stat"><span>Tareas abiertas</span><strong><?= (int) $stats['open_tasks'] ?></strong></div>
    <div class="stat"><span>Stock bajo</span><strong><?= (int) $stats['low_stock'] ?></strong></div>
    <div class="stat"><span>Rutinas próximas</span><strong><?= (int) $stats['upcoming_routines'] ?></strong></div>
</div>

<div class="actions" style="margin-bottom:1rem">
    <a class="btn" href="<?= e(url('/properties/create')) ?>">+ Nueva propiedad</a>
    <a class="btn btn-secondary" href="<?= e(url('/properties')) ?>">Ver todas</a>
</div>

<?php if (!$properties): ?>
    <div class="empty">Todavía no tenés propiedades. Creá la primera para empezar.</div>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($properties as $p): ?>
            <a class="panel" href="<?= e(url('/properties/' . $p['public_id'])) ?>" style="text-decoration:none;color:inherit;margin:0">
                <h3 style="margin:0 0 .35rem"><?= e($p['name']) ?></h3>
                <p class="muted" style="margin:0"><?= e($p['type']) ?> · <?= e($p['role']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="tip-card" style="margin-top:1rem">
    <img src="<?= e(asset('icons/plant-books.svg')) ?>" alt="">
    <div>
        <p>Espacios cuidados, vidas más plenas.</p>
        <p class="muted" style="margin-top:.35rem;font-family:var(--font-body);font-size:.9rem">Un pequeño cuidado hoy, un gran bienestar mañana.</p>
    </div>
</div>
