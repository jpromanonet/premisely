<div class="grid" style="margin-bottom:1.2rem">
  <div class="panel"><div class="muted">Propiedades</div><div class="stat-value"><?= (int) $stats['properties'] ?></div></div>
  <div class="panel"><div class="muted">Tareas vencidas</div><div class="stat-value"><?= (int) $stats['tasks_overdue'] ?></div></div>
  <div class="panel"><div class="muted">Stock bajo</div><div class="stat-value"><?= (int) $stats['low_stock'] ?></div></div>
  <div class="panel"><div class="muted">Valor inventario</div><div class="stat-value"><?= e(number_format((float) $stats['inventory_value'], 0, ',', '.')) ?></div></div>
</div>
<div class="form-actions" style="margin-bottom:1rem">
  <a class="btn" href="<?= e(url('/properties/create')) ?>">Nueva propiedad</a>
</div>
<?php if (!$properties): ?>
  <div class="empty">Todavía no tenés propiedades. Creá la primera para empezar.</div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($properties as $p): ?>
      <a class="panel" href="<?= e(url('/properties/' . $p['public_id'])) ?>" style="text-decoration:none;color:inherit">
        <h3><?= e($p['name']) ?></h3>
        <p class="muted"><?= e($p['type']) ?> · rol <?= e($p['role']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
