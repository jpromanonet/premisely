<?php
$p = $property ?? [];
$isEdit = !empty($p['public_id']);
$action = $isEdit ? url('/properties/' . $p['public_id']) : url('/properties');
$types = [
    'casa' => 'Casa',
    'departamento' => 'Departamento',
    'oficina' => 'Oficina',
    'local' => 'Local',
    'quinta' => 'Quinta',
    'casa_vacaciones' => 'Casa de vacaciones',
    'otra' => 'Otra',
];
$tenures = [
    'propia' => 'Propia',
    'alquiler' => 'Alquiler',
];
?>
<div class="page-header">
  <div><h1><?= e($title ?? ($isEdit ? 'Editar propiedad' : 'Nueva propiedad')) ?></h1></div>
  <a class="btn btn--ghost" href="<?= e($isEdit ? url('/properties/' . $p['public_id'] . '/dashboard') : url('/properties')) ?>">← Volver</a>
</div>
<section class="panel">
<form method="post" action="<?= e($action) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Nombre <input name="name" value="<?= e($p['name'] ?? '') ?>" required></label>
    <label>Tipo
      <select name="type">
        <?php foreach ($types as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= (($p['type'] ?? 'casa') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Tenencia
      <select name="tenure">
        <?php foreach ($tenures as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= (($p['tenure'] ?? 'propia') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>DirecciÃ³n <input name="address" value="<?= e($p['address'] ?? '') ?>"></label>
    <label>Moneda <input name="currency" value="<?= e($p['currency'] ?? 'ARS') ?>" maxlength="3" required></label>
    <label>Superficie mÂ² <input name="area_m2" value="<?= e((string) ($p['area_m2'] ?? '')) ?>"></label>
    <label>Ambientes <input name="rooms" value="<?= e((string) ($p['rooms'] ?? '')) ?>"></label>
    <label>Administrada desde <input type="date" name="managed_since" value="<?= e($p['managed_since'] ?? '') ?>"></label>
    <?php if ($isEdit): ?>
      <label>Estado
        <select name="status">
          <?php foreach (['activa' => 'Activa', 'pausada' => 'Pausada', 'temporal' => 'Temporal', 'archivada' => 'Archivada'] as $s => $sl): ?>
            <option value="<?= e($s) ?>" <?= (($p['status'] ?? '') === $s) ? 'selected' : '' ?>><?= e($sl) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
  </div>
  <label>DescripciÃ³n <textarea name="description"><?= e($p['description'] ?? '') ?></textarea></label>
  <label>Notas <textarea name="notes"><?= e($p['notes'] ?? '') ?></textarea></label>
  <div class="actions">
    <button class="btn" type="submit">Guardar</button>
  </div>
</form>
<?php if ($isEdit): ?>
<form method="post" action="<?= e(url('/properties/' . $p['public_id'] . '/archive')) ?>" onsubmit="return confirm('Â¿Archivar propiedad?');" style="margin-top:1rem">
  <?= csrf_field() ?>
  <button class="btn btn--ghost" type="submit">Archivar</button>
</form>
<?php endif; ?>
</section>
<?php if ($isEdit): ?>
<section id="eliminar" class="panel" style="margin-top:1.5rem;border-color:var(--danger-fg)">
  <h2>Eliminar permanentemente</h2>
  <p class="muted">Esto borra la propiedad y todos sus datos (espacios, inventario, stock, tareas, documentos, etc.). No se puede deshacer.</p>
  <form method="post" action="<?= e(url('/properties/' . $p['public_id'] . '/delete')) ?>" class="stack" onsubmit="return confirm('Â¿Eliminar esta propiedad para siempre?');">
    <?= csrf_field() ?>
    <label>EscribÃ­ el nombre exacto para confirmar
      <input name="confirm_name" placeholder="<?= e($p['name'] ?? '') ?>" required autocomplete="off">
    </label>
    <button class="btn btn--danger" type="submit">Eliminar propiedad</button>
  </form>
</section>
<?php endif; ?>
