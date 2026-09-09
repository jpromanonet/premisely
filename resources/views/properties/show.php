<?php
/** @var array<string,mixed> $property */
/** @var array<string,int> $stats */
/** @var list<array<string,mixed>> $activity */
/** @var bool $canEdit */
?>
<div class="page-header">
    <div>
        <h1><?= e($property['name']) ?></h1>
        <p><?= e((string) ($property['address'] ?? 'Sin dirección')) ?> · <?= e($property['type']) ?></p>
    </div>
    <div class="actions">
        <a class="btn" href="<?= e(url('/properties/' . $property['public_id'] . '/dashboard')) ?>">Panel</a>
        <?php if (!empty($canEdit)): ?>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $property['public_id'] . '/edit')) ?>">Editar</a>
        <?php endif; ?>
    </div>
</div>
<div class="card-grid">
    <div class="stat"><strong><?= (int) $stats['spaces'] ?></strong><span>Espacios</span></div>
    <div class="stat"><strong><?= (int) $stats['inventory'] ?></strong><span>Inventario</span></div>
    <div class="stat"><strong><?= (int) $stats['stock'] ?></strong><span>Stock</span></div>
    <div class="stat"><strong><?= (int) $stats['tasks'] ?></strong><span>Tareas abiertas</span></div>
</div>
<section class="panel">
    <h2>Descripción</h2>
    <p><?= nl2br(e((string) ($property['description'] ?? 'Sin descripción'))) ?></p>
</section>
<section class="panel">
    <h2>Actividad</h2>
    <?php if ($activity === []): ?>
        <p class="muted">Sin actividad.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($activity as $a): ?>
                <li><?= e((string) ($a['user_name'] ?? 'Sistema')) ?> · <?= e($a['action']) ?> · <?= e($a['entity_type']) ?>
                    <span class="muted"><?= e($a['created_at']) ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
