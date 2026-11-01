<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed>|null $fixed */
/** @var list<array<string,mixed>> $categories */
/** @var array<string,string> $frequencies */
$pid = $property['public_id'];
$isEdit = !empty($fixed['public_id']);
$action = $isEdit
    ? url('/properties/' . $pid . '/expenses/fixed/' . $fixed['public_id'])
    : url('/properties/' . $pid . '/expenses/fixed');
?>
<div class="page-header">
    <div><h1><?= e($title ?? ($isEdit ? 'Editar gasto fijo' : 'Nuevo gasto fijo')) ?></h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e($action) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="title" value="<?= e((string) ($fixed['title'] ?? '')) ?>" required></label>
            <label>Monto <input type="number" step="0.01" name="amount" value="<?= e((string) ($fixed['amount'] ?? '')) ?>" required></label>
            <label>Moneda <input name="currency" maxlength="3" value="<?= e((string) ($fixed['currency'] ?? $property['currency'] ?? 'ARS')) ?>"></label>
            <label>Frecuencia
                <select name="frequency_type" required>
                    <?php foreach ($frequencies as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= (($fixed['frequency_type'] ?? 'monthly') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Próximo vencimiento <input type="date" name="next_due_date" value="<?= e((string) ($fixed['next_due_date'] ?? '')) ?>"></label>
            <label>Categoría
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ((int) ($fixed['category_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($isEdit): ?>
                <label>Estado
                    <select name="is_active">
                        <option value="1" <?= !empty($fixed['is_active']) ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= empty($fixed['is_active']) ? 'selected' : '' ?>>Pausado</option>
                    </select>
                </label>
            <?php endif; ?>
        </div>
        <div class="actions">
            <button class="btn" type="submit"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">Cancelar</a>
        </div>
    </form>
</section>
<?php if ($isEdit): ?>
<section class="panel" style="margin-top:1.5rem;border-color:var(--danger-fg)">
    <h2>Eliminar gasto fijo</h2>
    <p class="muted">Deja de mostrar este gasto fijo. No borra los gastos ya registrados.</p>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses/fixed/' . $fixed['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este gasto fijo?');">
        <?= csrf_field() ?>
        <button class="btn btn--danger" type="submit">Eliminar</button>
    </form>
</section>
<?php endif; ?>
