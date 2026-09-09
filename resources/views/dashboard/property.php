<?php $pid = $property['public_id']; ?>
<div class="grid" style="margin-bottom:1.2rem">
  <div class="panel"><div class="muted">Tareas abiertas</div><div class="stat-value"><?= (int) $stats['open_tasks'] ?></div></div>
  <div class="panel"><div class="muted">Stock bajo</div><div class="stat-value"><?= (int) $stats['low_stock'] ?></div></div>
  <div class="panel"><div class="muted">Compras pendientes</div><div class="stat-value"><?= (int) $stats['shopping_pending'] ?></div></div>
  <div class="panel"><div class="muted">Gastos del mes</div><div class="stat-value"><?= e(number_format((float) $stats['month_expenses'], 0, ',', '.')) ?></div></div>
</div>
<div class="grid">
  <div class="panel">
    <h3>Tareas</h3>
    <?php if (!$tasks): ?><p class="muted">Sin tareas pendientes.</p><?php endif; ?>
    <ul>
      <?php foreach ($tasks as $t): ?>
        <li><?= e($t['title']) ?><?php if ($t['due_date']): ?> <span class="badge"><?= e($t['due_date']) ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">Ver tareas</a>
  </div>
  <div class="panel">
    <h3>Rutinas próximas</h3>
    <?php if (!$dueSoon): ?><p class="muted">Nada próximo.</p><?php endif; ?>
    <ul>
      <?php foreach ($dueSoon as $r): ?>
        <li><?= e($r['title']) ?> <span class="muted"><?= e($r['next_due_at']) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="panel">
    <h3>Actividad reciente</h3>
    <?php if (!$activity): ?><p class="muted">Sin actividad.</p><?php endif; ?>
    <ul>
      <?php foreach ($activity as $a): ?>
        <li><span class="muted"><?= e($a['created_at']) ?></span> <?= e($a['action']) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<p class="form-actions">
  <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/edit')) ?>">Editar propiedad</a>
  <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/search')) ?>">Buscar</a>
</p>
