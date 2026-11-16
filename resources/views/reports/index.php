<?php
/** @var array<string,mixed> $property */
/** @var string $month */
/** @var array<string,mixed> $inventory */
/** @var array<string,mixed> $stock */
/** @var list<array<string,mixed>> $fixedExpenses */
/** @var float $fixedTotal */
/** @var int $tasksDone */
/** @var int $habitsDone */
/** @var int $shopping */
$pid = $property['public_id'];
$fixedExpenses = $fixedExpenses ?? [];
$fixedTotal = (float) ($fixedTotal ?? 0);
$freqLabels = ['monthly' => 'Mensual', 'bimonthly' => 'Bimestral', 'quarterly' => 'Trimestral', 'yearly' => 'Anual'];
$currency = (string) ($property['currency'] ?? 'ARS');
$statusLabels = [
    'activo' => 'En casa',
    'en_reparacion' => 'En reparación',
    'prestado' => 'Prestado',
    'archivado' => 'Archivado',
];
$barColors = ['#0E6E6A', '#E38B3A', '#3D7A9A', '#7A6BB5', '#1C8A58', '#C44545'];
$statusMax = 0;
foreach ($inventory['by_status'] as $row) {
    $statusMax = max($statusMax, (int) $row['total']);
}
$stockOk = max(0, (int) $stock['count'] - (int) $stock['low']);
$stockTotal = max(1, (int) $stock['count']);
?>
<div class="page-header">
    <div>
        <h1>Reportes</h1>
        <p>Gastos fijos, stock y lo que se hizo en el mes.</p>
    </div>
    <form method="get" action="<?= e(form_target()) ?>" class="actions">
        <input type="hidden" name="r" value="/properties/<?= e($pid) ?>/reports">
        <input type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()">
    </form>
</div>

<div class="metric-grid">
    <div class="metric">
        <span>Gastos fijos</span>
        <strong><?= e($currency) ?> <?= e(format_money($fixedTotal)) ?></strong>
        <p><?= count($fixedExpenses) ?> en este mes</p>
    </div>
    <div class="metric">
        <span>Inventario</span>
        <strong><?= (int) $inventory['count'] ?></strong>
        <p><?= e($currency) ?> <?= e(format_money($inventory['value'])) ?></p>
    </div>
    <div class="metric">
        <span>En cero</span>
        <strong><?= (int) $stock['low'] ?></strong>
        <p>de <?= (int) $stock['count'] ?> en stock</p>
    </div>
    <div class="metric">
        <span>Hecho en el mes</span>
        <strong><?= (int) $tasksDone + (int) $habitsDone ?></strong>
        <p><?= (int) $tasksDone ?> tareas · <?= (int) $habitsDone ?> hábitos</p>
    </div>
</div>

<section class="panel">
    <div class="section-head">
        <h2>Gastos fijos del mes</h2>
        <a href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">Ver gastos fijos</a>
    </div>
    <?php if ($fixedExpenses === []): ?>
        <p class="empty-note">Sin gastos fijos para este mes.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nombre</th><th>Categoría</th><th>Frecuencia</th><th>Monto</th></tr></thead>
                <tbody>
                <?php foreach ($fixedExpenses as $f): ?>
                    <tr>
                        <td><?= e((string) $f['title']) ?></td>
                        <td><?= e((string) ($f['category_name'] ?? '—')) ?></td>
                        <td><?= e($freqLabels[$f['frequency_type'] ?? ''] ?? (string) ($f['frequency_type'] ?? '')) ?></td>
                        <td><?= e(format_money($f['amount'])) ?> <?= e((string) ($f['currency'] ?? $currency)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="report-grid">
    <section class="panel">
        <h2>Inventario</h2>
        <?php if ($inventory['by_status'] === []): ?>
            <p class="empty-note">Sin objetos cargados.</p>
        <?php else: ?>
            <?php $invTotal = max(1, (int) $inventory['count']); ?>
            <div class="report-split" aria-hidden="true">
                <?php foreach ($inventory['by_status'] as $i => $row): ?>
                    <span style="width: <?= (int) round((int) $row['total'] / $invTotal * 100) ?>%; background: <?= e($barColors[$i % count($barColors)]) ?>"></span>
                <?php endforeach; ?>
            </div>
            <p class="report-legend">
                <?php foreach ($inventory['by_status'] as $i => $row): ?>
                    <i style="background: <?= e($barColors[$i % count($barColors)]) ?>"></i>
                    <?= e($statusLabels[$row['status']] ?? ucfirst((string) $row['status'])) ?> <?= (int) $row['total'] ?>
                <?php endforeach; ?>
            </p>
            <p class="report-legend"><?= e($currency) ?> <?= e(format_money($inventory['value'])) ?> en valor estimado</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Stock</h2>
        <div class="report-split" aria-hidden="true">
            <span class="is-ok" style="width: <?= (int) round($stockOk / $stockTotal * 100) ?>%"></span>
            <span class="is-low" style="width: <?= (int) round((int) $stock['low'] / $stockTotal * 100) ?>%"></span>
        </div>
        <p class="report-legend">
            <i class="is-ok"></i> <?= $stockOk ?> con stock
            <i class="is-low"></i> <?= (int) $stock['low'] ?> en cero
            <i style="background:#3D7A9A"></i> <?= (int) $shopping ?> en compras
        </p>
    </section>
</div>
