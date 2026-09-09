<?php
/** @var array<string,mixed> $property */
/** @var string $weekStart */
/** @var string $weekEnd */
/** @var array<string,array<string,list<array<string,mixed>>>> $days */
/** @var string $prev */
/** @var string $next */
$pid = $property['public_id'];
$labels = [
    'tasks' => 'Tareas',
    'routines' => 'Rutinas / limpieza',
    'laundry' => 'Lavandería',
    'meals' => 'Comidas',
    'maintenance' => 'Mantenimiento',
    'services' => 'Servicios',
];
?>
<div class="page-header">
    <div>
        <h1>Planificación semanal</h1>
        <p>Vista operativa de la semana.</p>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/planning', ['week' => $prev])) ?>">←</a>
        <span class="mono"><?= e($weekStart) ?> → <?= e($weekEnd) ?></span>
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/planning', ['week' => $next])) ?>">→</a>
    </div>
</div>

<div class="grid">
<?php foreach ($days as $date => $buckets): ?>
    <section class="panel" style="margin:0">
        <h3 class="mono"><?= e($date) ?></h3>
        <?php
        $empty = true;
        foreach ($buckets as $rows) { if ($rows !== []) { $empty = false; break; } }
        ?>
        <?php if ($empty): ?>
            <p class="muted">Sin actividad.</p>
            <a class="btn btn-secondary btn--sm" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">+ Tarea</a>
        <?php else: ?>
            <?php foreach ($labels as $key => $label): ?>
                <?php if (empty($buckets[$key])) continue; ?>
                <div style="margin:.55rem 0">
                    <div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em"><?= e($label) ?></div>
                    <?php foreach ($buckets[$key] as $row): ?>
                        <div style="padding:.25rem 0"><?= e($row['title'] ?? $row['name'] ?? '—') ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
</div>
