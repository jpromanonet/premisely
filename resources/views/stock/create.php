<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $categories */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nuevo ítem</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/stock')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/stock')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Cantidad <input type="number" step="0.001" name="quantity" value="0"></label>
            <label>Unidad <input name="unit" value="u"></label>
            <label>Mínimo <input type="number" step="0.001" name="minimum_quantity" value="0"></label>
            <label>Objetivo <input type="number" step="0.001" name="target_quantity"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Categoría
                <select name="category_id"><option value="">—</option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><span>Auto agregar a compras</span> <input type="checkbox" name="auto_add_to_shopping" value="1"></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/stock')) ?>">Cancelar</a>
        </div>
    </form>
</section>
