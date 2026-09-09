<?php $pid = $property['public_id']; ?>
<div class="page-hero">
    <div class="page-hero__copy">
        <h1><?= e($property['name'] ?? 'Propiedad') ?></h1>
        <p>Tu hogar, mejor organizado. Resumen operativo de hoy.</p>
    </div>
    <div class="page-hero__art">
        <img src="<?= e(asset('icons/hero-window.svg')) ?>" alt="">
        <div class="motto-chip">Espacios que cuidás. Historias que continúan.</div>
    </div>
</div>

<div class="card-grid">
    <div class="stat">
        <span>Tareas abiertas</span>
        <strong><?= (int) $stats['open_tasks'] ?></strong>
        <p class="muted" style="margin:.35rem 0 0"><a href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">Ver tareas →</a></p>
    </div>
    <div class="stat">
        <span>Stock bajo</span>
        <strong><?= (int) $stats['low_stock'] ?></strong>
        <p class="muted" style="margin:.35rem 0 0"><a href="<?= e(url('/properties/' . $pid . '/stock')) ?>">Ver stock →</a></p>
    </div>
    <div class="stat">
        <span>Compras pendientes</span>
        <strong><?= (int) $stats['shopping_pending'] ?></strong>
        <p class="muted" style="margin:.35rem 0 0"><a href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">Lista →</a></p>
    </div>
    <div class="stat">
        <span>Gastos del mes</span>
        <strong><?= e(number_format((float) $stats['month_expenses'], 0, ',', '.')) ?></strong>
        <p class="muted" style="margin:.35rem 0 0"><a href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">Ver gastos →</a></p>
    </div>
    <div class="stat">
        <span>Reparaciones abiertas</span>
        <strong><?= (int) ($stats['open_repairs'] ?? 0) ?></strong>
        <p class="muted" style="margin:.35rem 0 0"><a href="<?= e(url('/properties/' . $pid . '/repairs')) ?>">Ver →</a></p>
    </div>
</div>

<div class="grid">
    <div class="panel">
        <h3>Tareas de hoy</h3>
        <?php if (!$tasks): ?>
            <p class="muted">Sin tareas pendientes. Todo en orden en esta propiedad.</p>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0">
                <?php foreach ($tasks as $t): ?>
                    <li style="display:flex;justify-content:space-between;gap:1rem;padding:.55rem 0;border-bottom:1px solid var(--line)">
                        <span><?= e($t['title']) ?></span>
                        <?php if (($t['status'] ?? '') === 'completed'): ?>
                            <span class="badge badge-ok">Completada</span>
                        <?php elseif (($t['status'] ?? '') === 'in_progress'): ?>
                            <span class="badge badge-info">En curso</span>
                        <?php else: ?>
                            <span class="badge badge-warn">Pendiente</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="actions"><a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">Ver todas</a></div>
    </div>

    <div class="panel">
        <h3>Rutinas próximas</h3>
        <?php if (!$dueSoon): ?>
            <p class="muted">Nada vencido ni próximo.</p>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0">
                <?php foreach ($dueSoon as $r): ?>
                    <li style="padding:.55rem 0;border-bottom:1px solid var(--line)">
                        <?= e($r['title']) ?>
                        <div class="muted mono"><?= e($r['next_due_at'] ?? '') ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="actions"><a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/routines')) ?>">Ver rutinas</a></div>
    </div>

    <div class="panel">
        <h3>Actividad reciente</h3>
        <?php if (!$activity): ?>
            <p class="muted">Sin actividad todavía.</p>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0">
                <?php foreach ($activity as $a): ?>
                    <li style="padding:.55rem 0;border-bottom:1px solid var(--line)">
                        <span class="muted mono"><?= e($a['created_at']) ?></span>
                        <div><?= e($a['action']) ?> <span class="muted">(<?= e($a['entity_type']) ?>)</span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="tip-card">
    <img src="<?= e(asset('icons/plant-books.svg')) ?>" alt="">
    <div>
        <p>Espacios cuidados, vidas más plenas.</p>
        <div class="actions">
            <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/edit')) ?>">Editar propiedad</a>
            <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>">Mantenimiento</a>
        </div>
    </div>
</div>
