<?php
/** @var array<string,mixed> $property */
/** @var string $month */
/** @var array<string,mixed> $inventory */
/** @var array<string,mixed> $stock */
/** @var array<string,mixed> $expenses */
/** @var list<array{month:string,total:float}> $trend */
/** @var int $tasksDone */
/** @var int $habitsDone */
/** @var int $shopping */
$pid = $property['public_id'];
$currency = (string) ($property['currency'] ?? 'ARS');
$monthNames = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
$statusLabels = [
    'activo' => 'En casa',
    'en_reparacion' => 'En reparación',
    'prestado' => 'Prestado',
    'archivado' => 'Archivado',
];
$barColors = ['#0E6E6A', '#E38B3A', '#3D7A9A', '#7A6BB5', '#1C8A58', '#C44545'];
$categoryMax = 0.0;
foreach ($expenses['by_category'] as $row) {
    $categoryMax = max($categoryMax, abs((float) $row['total']));
}
$trendMax = 0.0;
foreach ($trend as $point) {
    $trendMax = max($trendMax, abs((float) $point['total']));
}
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
        <p>Gastos, stock y lo que se hizo en el mes.</p>
    </div>
    <form method="get" action="<?= e(form_target()) ?>" class="actions">
        <input type="hidden" name="r" value="/properties/<?= e($pid) ?>/reports">
        <input type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()">
    </form>
</div>

<div class="metric-grid">
    <div class="metric">
        <span>Gastos</span>
        <strong><?= e($currency) ?> <?= e(format_money($expenses['month_total'])) ?></strong>
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

<div class="report-grid">
    <section class="panel">
        <h2>Gastos por categoría</h2>
        <?php if ($expenses['by_category'] === []): ?>
            <p class="empty-note">Sin gastos este mes.</p>
        <?php else: ?>
            <div class="report-bars">
                <?php foreach ($expenses['by_category'] as $i => $row): ?>
                    <?php
                    $amount = (float) $row['total'];
                    $width = $categoryMax > 0 ? (int) round(abs($amount) / $categoryMax * 100) : 0;
                    ?>
                    <div class="report-bar">
                        <div class="report-bar__top">
                            <span><?= e((string) $row['category_name']) ?></span>
                            <strong><?= e(format_money($amount)) ?></strong>
                        </div>
                        <div class="report-bar__track" aria-hidden="true">
                            <span style="width: <?= $width ?>%; background: <?= e($barColors[$i % count($barColors)]) ?>"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Últimos 6 meses</h2>
        <div class="report-cols" role="img" aria-label="Gastos de los últimos seis meses">
            <?php foreach ($trend as $point): ?>
                <?php
                $parts = explode('-', $point['month']);
                $label = $monthNames[(int) ($parts[1] ?? 1)] ?? $point['month'];
                $height = $trendMax > 0 ? (int) round(abs((float) $point['total']) / $trendMax * 100) : 0;
                $current = $point['month'] === $month;
                ?>
                <div class="report-col<?= $current ? ' is-current' : '' ?>">
                    <b><?= $height > 0 ? e(number_format((float) $point['total'] / 1000, 0, ',', '.')) . 'k' : '' ?></b>
                    <div class="report-col__track"><span style="height: <?= $height ?>%"></span></div>
                    <small><?= e($label) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

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
