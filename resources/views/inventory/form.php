<?php
$item = $item ?? [];
$pid = $property['public_id'];
$isEdit = !empty($item['public_id']);
$action = $isEdit
  ? url('/properties/' . $pid . '/inventory/' . $item['public_id'])
  : url('/properties/' . $pid . '/inventory');
?>
<div class="page-header"><div><h1><?= e($title ?? ($isEdit ? 'Editar objeto' : 'Nuevo objeto')) ?></h1></div></div>
<section class="panel">
<form method="post" action="<?= e($action) ?>" class="stack">
  <?= csrf_field() ?>
  <label>Nombre <input name="name" value="<?= e($item['name'] ?? '') ?>" required></label>
  <label>Descripción <textarea name="description"><?= e($item['description'] ?? '') ?></textarea></label>
  <div class="form-grid">
    <label>Espacio
      <select name="space_id"><option value="">—</option>
        <?php foreach ($spaces as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= ((int)($item['space_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Categoría
      <select name="category_id"><option value="">—</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)($item['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Propietario (integrante)
      <select name="owner_member_id"><option value="">—</option>
        <?php foreach ($members as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= ((int)($item['owner_member_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>><?= e($m['display_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Marca <input name="brand" value="<?= e($item['brand'] ?? '') ?>"></label>
    <label>Modelo <input name="model" value="<?= e($item['model'] ?? '') ?>"></label>
    <label>Nº serie <input name="serial_number" value="<?= e($item['serial_number'] ?? '') ?>"></label>
    <label>Precio compra <input name="purchase_price" value="<?= e((string)($item['purchase_price'] ?? '')) ?>"></label>
    <label>Valor estimado <input name="estimated_value" value="<?= e((string)($item['estimated_value'] ?? '')) ?>"></label>
    <label>Condición <input name="condition" value="<?= e($item['condition'] ?? 'bueno') ?>"></label>
    <label>Garantía hasta <input type="date" name="warranty_until" value="<?= e($item['warranty_until'] ?? '') ?>"></label>
  </div>
  <button class="btn" type="submit">Guardar</button>
</form>
</section>
