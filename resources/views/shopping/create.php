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
            <label>Unidad <input name="unit" value="u"></label>
            <label>Precio estimado <input type="number" step="0.01" name="estimated_price"></label>
            <label>Stock relacionado
                <select name="stock_item_id">
                    <option value="">—</option>
                    <?php foreach ($stockItems as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridad
                <select name="priority">
                    <?php foreach (['low','normal','high'] as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Agregar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">Cancelar</a>
        </div>
    </form>
</section>
