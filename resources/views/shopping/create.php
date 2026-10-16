<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $stockItems */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Agregar ítem</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Cantidad <input type="number" step="0.001" name="quantity" value="1"></label>
            <label>Unidad <input name="unit" value="u" placeholder="kg, u, paquete"></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Agregar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">Cancelar</a>
        </div>
    </form>
</section>
