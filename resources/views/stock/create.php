<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $categories */
$pid = $property['public_id'];
$categories = $categories ?? [];
?>
<div class="page-header">
    <div>
        <h1>Nuevo en stock</h1>
        <p>Lo que ya hay en casa.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/stock')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/stock')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required placeholder="Sal, papel, detergente"></label>
            <label>Marca <input name="brand" placeholder="Opcional"></label>
            <label>Cantidad <input type="number" step="1" min="0" name="quantity" value="1"></label>
            <label>Unidad <input name="unit" placeholder="paquetes, kg, unidades"></label>
            <label>Rubro
                <select name="category_id">
                    <option value="">Sin rubro</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/stock')) ?>">Cancelar</a>
        </div>
    </form>
</section>
