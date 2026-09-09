<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $item */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $categories */
/** @var list<array<string,mixed>> $members */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Editar ítem</h1></div></div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e($item['name']) ?>" required></label>
            <label>Marca <input name="brand" value="<?= e((string)$item['brand']) ?>"></label>
            <label>Modelo <input name="model" value="<?= e((string)$item['model']) ?>"></label>
            <label>Serie <input name="serial_number" value="<?= e((string)$item['serial_number']) ?>"></label>
            <label>Espacio
                <select name="space_id">
                    <option value="">—</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (int)($item['space_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Categoría
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)($item['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Dueño
                <select name="owner_member_id">
                    <option value="">—</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= (int)($item['owner_member_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Condición
                <select name="condition">
                    <?php foreach (['nuevo','bueno','regular','malo'] as $c): ?>
                        <option value="<?= $c ?>" <?= $item['condition'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Estado
                <select name="status">
                    <?php foreach (['activo','en_reparacion','prestado','archivado'] as $s): ?>
                        <option value="<?= $s ?>" <?= $item['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Propiedad de
                <select name="ownership_type">
                    <?php foreach (['propiedad','personal','compartido','prestado'] as $o): ?>
                        <option value="<?= $o ?>" <?= $item['ownership_type'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Precio compra <input type="number" step="0.01" name="purchase_price" value="<?= e((string)$item['purchase_price']) ?>"></label>
            <label>Fecha compra <input type="date" name="purchase_date" value="<?= e((string)$item['purchase_date']) ?>"></label>
            <label>Garantía hasta <input type="date" name="warranty_until" value="<?= e((string)$item['warranty_until']) ?>"></label>
        </div>
        <label>Descripción <textarea name="description"><?= e((string)$item['description']) ?></textarea></label>
        <button class="btn" type="submit">Guardar</button>
    </form>
</section>
