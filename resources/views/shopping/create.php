<?php
/** @var array<string,mixed> $property */
/** @var array<string,string> $rubros */
$pid = $property['public_id'];
$rubros = $rubros ?? shopping_rubros();
?>
<div class="page-header">
    <div>
        <h1>Nuevo en compras</h1>
        <p>Lo que falta comprar.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Producto <input name="name" required placeholder="Sal, papel, detergente"></label>
            <label>Marca <input name="brand" placeholder="Opcional"></label>
            <label>Rubro
                <select name="category">
                    <option value="">Sin rubro</option>
                    <?php foreach ($rubros as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Cantidad a comprar <input type="number" step="1" min="1" name="quantity" value="1"></label>
            <label>Unidad <input name="unit" placeholder="paquetes, kg, unidades"></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">Cancelar</a>
        </div>
    </form>
</section>
