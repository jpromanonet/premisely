<?php
$p = $property ?? [];
$isEdit = !empty($p['public_id']);
$action = $isEdit ? url('/properties/' . $p['public_id']) : url('/properties');
?>
<div class="page-header"><div><h1><?= e($title ?? ($isEdit ? 'Editar propiedad' : 'Nueva propiedad')) ?></h1></div></div>
<section class="panel">
<form method="post" action="<?= e($action) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Nombre <input name="name" value="<?= e($p['name'] ?? '') ?>" required></label>
    <label>Tipo
      <select name="type">
        <?php foreach (['casa','departamento','oficina','local','quinta','casa_vacaciones','otra','otro'] as $t): ?>
          <option value="<?= e($t) ?>" <?= (($p['type'] ?? 'casa') === $t) ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Dirección <input name="address" value="<?= e($p['address'] ?? '') ?>"></label>
    <label>Moneda <input name="currency" value="<?= e($p['currency'] ?? 'ARS') ?>" maxlength="3" required></label>
    <label>Superficie m² <input name="area_m2" value="<?= e((string) ($p['area_m2'] ?? '')) ?>"></label>
    <label>Ambientes <input name="rooms" value="<?= e((string) ($p['rooms'] ?? '')) ?>"></label>
    <label>Administrada desde <input type="date" name="managed_since" value="<?= e($p['managed_since'] ?? '') ?>"></label>
    <?php if ($isEdit): ?>
      <label>Estado
        <select name="status">
          <?php foreach (['activa','pausada','temporal','archivada'] as $s): ?>
            <option value="<?= e($s) ?>" <?= (($p['status'] ?? '') === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
  </div>
  <label>Descripción <textarea name="description"><?= e($p['description'] ?? '') ?></textarea></label>
  <label>Notas <textarea name="notes"><?= e($p['notes'] ?? '') ?></textarea></label>
  <div class="actions">
    <button class="btn" type="submit">Guardar</button>
  </div>
</form>
<?php if ($isEdit): ?>
<form method="post" action="<?= e(url('/properties/' . $p['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Archivar propiedad?');" style="margin-top:1rem">
  <?= csrf_field() ?>
  <button class="btn btn--ghost" type="submit">Archivar</button>
</form>
<?php endif; ?>
</section>
<?php if ($isEdit): ?>
<section id="eliminar" class="panel" style="margin-top:1.5rem;border-color:var(--danger-fg)">
  <h2>Eliminar permanentemente</h2>
  <p class="muted">Esto borra la propiedad y todos sus datos (espacios, inventario, stock, tareas, documentos, etc.). No se puede deshacer.</p>
  <form method="post" action="<?= e(url('/properties/' . $p['public_id'] . '/delete')) ?>" class="stack" onsubmit="return confirm('¿Eliminar esta propiedad para siempre?');">
    <?= csrf_field() ?>
    <label>Escribí el nombre exacto para confirmar
      <input name="confirm_name" placeholder="<?= e($p['name'] ?? '') ?>" required autocomplete="off">
    </label>
    <button class="btn btn--danger" type="submit">Eliminar propiedad</button>
  </form>
</section>
<?php endif; ?>
