<?php
/** @var array<string,mixed> $property */
/** @var string $month */
/** @var array<string,mixed> $inventory */
/** @var array<string,mixed> $stock */
/** @var array<string,mixed> $expenses */
/** @var array<string,mixed> $ops */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Reportes</h1><p>Resumen simple de inventario, stock, gastos y operación.</p></div>
    <form method="get" action="<?= e(url('/properties/' . $pid . '/reports')) ?>" class="actions">
        <input type="month" name="month" value="<?= e($month) ?>">
        <button class="btn btn-secondary" type="submit">Filtrar</button>
    </form>
</div>

<div class="card-grid">
    <div class="stat"><span>Objetos</span><strong><?= (int)$inventory['count'] ?></strong></div>
    <div class="stat"><span>Valor estimado</span><strong><?= e(number_format((float)$inventory['value'], 0, ',', '.')) ?></strong></div>
    <div class="stat"><span>Stock bajo</span><strong><?= (int)$stock['low'] ?></strong> / <?= (int)$stock['count'] ?></div>
    <div class="stat"><span>Gastos <?= e($month) ?></span><strong><?= e(number_format((float)$expenses['month_total'], 0, ',', '.')) ?></strong></div>
</div>

<div class="grid">
    <section class="panel">
        <h3>Inventario por estado</h3>
        <ul>
            <?php foreach ($inventory['by_status'] as $row): ?>
                <li><?= e($row['status']) ?> · <strong><?= (int)$row['total'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$inventory['by_status']): ?><p class="muted">Sin datos.</p><?php endif; ?>
    </section>
    <section class="panel">
        <h3>Gastos por categoría</h3>
        <ul>
            <?php foreach ($expenses['by_category'] as $row): ?>
                <li><?= e($row['category_name']) ?> · <strong><?= e(number_format((float)$row['total'], 0, ',', '.')) ?></strong></li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$expenses['by_category']): ?><p class="muted">Sin gastos este mes.</p><?php endif; ?>
    </section>
    <section class="panel">
        <h3>Operación</h3>
        <ul>
            <li>Tareas completadas (mes): <strong><?= (int)$ops['tasks_done'] ?></strong></li>
            <li>Rutinas vencidas: <strong><?= (int)$ops['routines_overdue'] ?></strong></li>
            <li>Mantenimientos (30 días): <strong><?= (int)$ops['maintenance_upcoming'] ?></strong></li>
            <li>Garantías por vencer (60 días): <strong><?= (int)$ops['warranties_expiring'] ?></strong></li>
        </ul>
    </section>
</div>
