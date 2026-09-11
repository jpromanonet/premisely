<?php
$item = $item ?? [];
$pid = $property['public_id'];
$isEdit = !empty($item['public_id']);
$action = $isEdit
  ? url('/properties/' . $pid . '/inventory/' . $item['public_id'])
  : url('/properties/' . $pid . '/inventory');
$back = $isEdit
  ? url('/properties/' . $pid . '/inventory/' . $item['public_id'])
  : url('/properties/' . $pid . '/inventory');
?>
<div class="page-header">
  <div><h1><?= e($title ?? ($isEdit ? 'Editar objeto' : 'Nuevo objeto')) ?></h1></div>
  <a class="btn btn--ghost" href="<?= e($back) ?>">← Volver</a>
</div>
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
    <label>Integrante
      <select name="owner_member_id"><option value="">—</option>
        <?php foreach ($members as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= ((int)($item['owner_member_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>><?= e($m['display_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Propiedad de
      <select name="ownership_type">
        <?php foreach (['propiedad','personal','compartido','prestado'] as $o): ?>
          <option value="<?= e($o) ?>" <?= (($item['ownership_type'] ?? 'propiedad') === $o) ? 'selected' : '' ?>><?= e(ucfirst($o)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Marca <input name="brand" value="<?= e($item['brand'] ?? '') ?>"></label>
    <label>Modelo <input name="model" value="<?= e($item['model'] ?? '') ?>"></label>
    <label>Nº serie <input name="serial_number" value="<?= e($item['serial_number'] ?? '') ?>"></label>
    <label>Código interno <input name="internal_code" value="<?= e($item['internal_code'] ?? '') ?>"></label>
    <label>Fecha de compra <input type="date" name="purchase_date" value="<?= e($item['purchase_date'] ?? '') ?>"></label>
    <label>Comercio <input name="purchase_store" value="<?= e($item['purchase_store'] ?? '') ?>"></label>
    <label>Precio de compra <input name="purchase_price" value="<?= e((string)($item['purchase_price'] ?? '')) ?>"></label>
    <label>Moneda compra <input name="purchase_currency" maxlength="3" value="<?= e($item['purchase_currency'] ?? ($property['currency'] ?? 'ARS')) ?>"></label>
    <label>Valor estimado <input name="estimated_value" value="<?= e((string)($item['estimated_value'] ?? '')) ?>"></label>
    <label>Moneda valor <input name="estimated_value_currency" maxlength="3" value="<?= e($item['estimated_value_currency'] ?? ($property['currency'] ?? 'ARS')) ?>"></label>
    <label>Condición
      <select name="condition">
        <?php foreach (['nuevo','bueno','regular','malo'] as $c): ?>
          <option value="<?= e($c) ?>" <?= (($item['condition'] ?? 'bueno') === $c) ? 'selected' : '' ?>><?= e(ucfirst($c)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Estado
      <select name="status">
        <?php foreach (['activo','en_reparacion','prestado','archivado'] as $s): ?>
          <option value="<?= e($s) ?>" <?= (($item['status'] ?? 'activo') === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Garantía hasta <input type="date" name="warranty_until" value="<?= e($item['warranty_until'] ?? '') ?>"></label>
  </div>
  <div class="actions">
    <button class="btn" type="submit">Guardar</button>
    <a class="btn btn--ghost" href="<?= e($back) ?>">Cancelar</a>
  </div>
</form>
</section>
