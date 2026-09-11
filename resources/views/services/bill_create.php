<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $service */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva factura</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/services')) ?>">← Volver</a>
</div>
<section class="panel">
    <p class="muted">Servicio: <strong><?= e($service['name']) ?></strong></p>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/services/' . $service['public_id'] . '/bills')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Período <input name="period" placeholder="2026-09"></label>
            <label>Monto <input type="number" step="0.01" name="amount" required></label>
            <label>Vence <input type="date" name="due_date"></label>
            <label>Pagado <input type="date" name="paid_at"></label>
            <label><span>Crear gasto (utilities)</span> <input type="checkbox" name="create_expense" value="1" checked></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Agregar factura</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/services')) ?>">Cancelar</a>
        </div>
    </form>
</section>
