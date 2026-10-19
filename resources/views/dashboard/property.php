<?php
/** @var array<string,mixed> $property */
/** @var string $today */
/** @var int $habitsDone */
/** @var int $habitsTotal */
/** @var int $todo */
/** @var list<string> $mealsFilled */
/** @var int $shopping */
/** @var int $lowStock */
/** @var int $inventory */
/** @var int $stockCount */
/** @var float $monthExpenses */
/** @var list<array<string,mixed>> $expenseCategories */
/** @var list<array{month:string,total:float}> $expenseTrend */
$pid = $property['public_id'];
$months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$weekdays = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
$heading = ucfirst($weekdays[(int) date('N')]) . ' ' . date('j') . ' de ' . $months[(int) date('n')];
$slots = ['desayuno' => 'Desayuno', 'almuerzo' => 'Almuerzo', 'merienda' => 'Merienda', 'cena' => 'Cena'];
$dayUrl = url('/properties/' . $pid . '/calendar', ['day' => $today]);
$habitPct = $habitsTotal > 0 ? (int) round($habitsDone / $habitsTotal * 100) : 0;
$currency = (string) ($property['currency'] ?? 'ARS');
$monthNames = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
$barColors = ['#0E6E6A', '#E38B3A', '#3D7A9A', '#7A6BB5', '#1C8A58', '#C44545'];
$categoryMax = 0.0;
foreach ($expenseCategories as $row) {
    $categoryMax = max($categoryMax, abs((float) $row['total']));
}
$trendMax = 0.0;
foreach ($expenseTrend as $point) {
    $trendMax = max($trendMax, abs((float) $point['total']));
}
$stockTotal = max(1, (int) $stockCount);
$stockOk = max(0, (int) $stockCount - (int) $lowStock);
$currentMonth = date('Y-m');
?>
<div class="page-header">
    <div>
        <p class="eyebrow"><?= e((string) ($property['name'] ?? 'La casa')) ?></p>
        <h1><?= e($heading) ?></h1>
        <p>El día está en el calendario. Acá solo ves cómo viene.</p>
    </div>
    <a class="btn" href="<?= e($dayUrl) ?>">Abrir el día</a>
</div>

<div class="today-grid">
    <a class="today-card" href="<?= e(url('/properties/' . $pid . '/routines')) ?>">
        <span>Hábitos</span>
        <strong><?= (int) $habitsDone ?> de <?= (int) $habitsTotal ?></strong>
        <div class="quest-progress" aria-hidden="true"><span style="width: <?= $habitPct ?>%"></span></div>
    </a>
    <a class="today-card" href="<?= e($dayUrl) ?>">
        <span>Para hacer</span>
        <strong><?= (int) $todo === 0 ? 'Nada pendiente' : (int) $todo ?></strong>
        <p><?= (int) $todo === 0 ? 'El día está libre de tareas.' : 'Tareas y reparaciones que siguen en el día.' ?></p>
    </a>
    <a class="today-card" href="<?= e($dayUrl) ?>">
        <span>Comidas</span>
        <ul class="meal-dots">
            <?php foreach ($slots as $slot => $label): ?>
                <li class="<?= in_array($slot, $mealsFilled, true) ? 'is-on meal-card--' . e($slot) : '' ?>"><?= e($label) ?></li>
            <?php endforeach; ?>
        </ul>
    </a>
</div>

<h2 class="section-label">Métricas</h2>
<div class="metric-grid">
    <a class="metric" href="<?= e(url('/properties/' . $pid . '/stock')) ?>">
        <span>Para reponer</span>
        <strong><?= (int) $lowStock ?></strong>
    </a>
    <a class="metric" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">
        <span>Compras</span>
        <strong><?= (int) $shopping ?></strong>
    </a>
    <a class="metric" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">
        <span>Gastos del mes</span>
        <strong><?= e($currency) ?> <?= e(format_money($monthExpenses)) ?></strong>
    </a>
    <a class="metric" href="<?= e(url('/properties/' . $pid . '/inventory')) ?>">
        <span>Inventario</span>
        <strong><?= (int) $inventory ?></strong>
    </a>
</div>

<div class="report-grid">
    <section class="panel">
        <h2>Gastos del semestre</h2>
        <div class="report-cols" role="img" aria-label="Gastos de los últimos seis meses">
            <?php foreach ($expenseTrend as $point): ?>
                <?php
                $parts = explode('-', $point['month']);
                $label = $monthNames[(int) ($parts[1] ?? 1)] ?? $point['month'];
                $height = $trendMax > 0 ? (int) round(abs((float) $point['total']) / $trendMax * 100) : 0;
                $current = $point['month'] === $currentMonth;
                ?>
                <div class="report-col<?= $current ? ' is-current' : '' ?>">
                    <b><?= $height > 0 ? e(number_format((float) $point['total'] / 1000, 0, ',', '.')) . 'k' : '' ?></b>
                    <div class="report-col__track"><span style="height: <?= $height ?>%"></span></div>
                    <small><?= e($label) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel">
        <h2>Este mes</h2>
        <?php if ($expenseCategories === []): ?>
            <p class="empty-note">Sin gastos este mes.</p>
        <?php else: ?>
            <div class="report-bars">
                <?php foreach ($expenseCategories as $i => $row): ?>
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
</div>

<section class="panel">
    <h2>Stock</h2>
    <div class="report-split" aria-hidden="true">
        <span class="is-ok" style="width: <?= (int) round($stockOk / $stockTotal * 100) ?>%"></span>
        <span class="is-low" style="width: <?= (int) round((int) $lowStock / $stockTotal * 100) ?>%"></span>
    </div>
    <p class="report-legend">
        <i class="is-ok"></i> <?= $stockOk ?> bien
        <i class="is-low"></i> <?= (int) $lowStock ?> para reponer
        <i style="background:#3D7A9A"></i> <?= (int) $shopping ?> en compras
    </p>
</section>
