<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $categories */
/** @var list<array<string,mixed>> $members */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Nuevo ítem de inventario</h1></div></div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/inventory')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Marca <input name="brand"></label>
            <label>Modelo <input name="model"></label>
            <label>Serie <input name="serial_number"></label>
            <label>Espacio
                <select name="space_id">
                    <option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Categoría
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Dueño
                <select name="owner_member_id">
                    <option value="">—</option>
                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['display_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Condición
                <select name="condition">
                    <?php foreach (['nuevo','bueno','regular','malo'] as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Propiedad de
                <select name="ownership_type">
                    <?php foreach (['propiedad','personal','compartido','prestado'] as $o): ?><option value="<?= $o ?>"><?= ucfirst($o) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Precio compra <input type="number" step="0.01" name="purchase_price"></label>
            <label>Fecha compra <input type="date" name="purchase_date"></label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <button class="btn" type="submit">Guardar</button>
    </form>
</section>
