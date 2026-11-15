<?php
/** @var array<string,mixed> $property */
/** @var string $day */
$pid = $property['public_id'];
$day = (string) ($day ?? date('Y-m-d'));
$back = '/properties/' . $pid . '/calendar?day=' . rawurlencode($day);
?>
<div class="page-header">
    <div>
        <h1>Gasto de la semana</h1>
        <p>Algo en lo que hay que gastar esta semana.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url($back)) ?>">← Volver al día</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/week-spend')) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="day" value="<?= e($day) ?>">
        <input type="hidden" name="redirect" value="<?= e($back) ?>">
        <div class="form-grid">
            <label>En qué hay que gastar <input name="title" required placeholder="Ej. supermercado, nafta"></label>
            <label>Precio <input type="number" name="amount" min="0.01" step="0.01" required></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Agregar</button>
            <a class="btn btn--ghost" href="<?= e(url($back)) ?>">Cancelar</a>
        </div>
    </form>
</section>
