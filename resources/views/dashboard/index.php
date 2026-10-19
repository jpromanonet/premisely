<div class="page-header">
    <div>
        <p class="eyebrow">Todas las casas</p>
        <h1>Resumen</h1>
        <p>Elegí una propiedad para ver el día.</p>
    </div>
    <a class="btn" href="<?= e(url('/properties/create')) ?>">+ Nueva propiedad</a>
</div>

<div class="card-grid">
    <div class="stat"><span>Propiedades</span><strong><?= (int) $stats['properties'] ?></strong></div>
    <div class="stat"><span>Tareas abiertas</span><strong><?= (int) $stats['open_tasks'] ?></strong></div>
    <div class="stat"><span>Stock bajo</span><strong><?= (int) $stats['low_stock'] ?></strong></div>
    <div class="stat"><span>Hábitos por vencer</span><strong><?= (int) $stats['upcoming_routines'] ?></strong></div>
</div>

<div class="actions" style="margin-bottom:1rem">
    <a class="btn btn-secondary" href="<?= e(url('/properties')) ?>">Ver todas</a>
</div>

<?php if (!$properties): ?>
    <div class="empty">Todavía no tenés propiedades. Creá la primera para empezar.</div>
<?php else: ?>
    <div class="property-stack">
        <?php foreach ($properties as $p): ?>
            <a class="property-card" href="<?= e(url('/properties/' . $p['public_id'] . '/dashboard')) ?>">
                <h3><?= e($p['name']) ?></h3>
                <p><?= e(property_type_label($p['type'] ?? null)) ?> · <?= e(property_tenure_label($p['tenure'] ?? null)) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
