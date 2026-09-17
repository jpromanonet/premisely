<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $repair */
/** @var list<array<string,mixed>> $spaces */
/** @var array<string,string> $statuses */
$pid = $property['public_id'];
$statuses = $statuses ?? ['pendiente' => 'Pendiente', 'en_curso' => 'En curso', 'hecho' => 'Hecho', 'cancelada' => 'Cancelada'];
?>
<div class="page-header">
    <div><h1>Editar reparación</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/repairs')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $repair['public_id'] . '/edit')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" value="<?= e((string) $repair['title']) ?>" required></label>
            <label>Reportada <input type="date" name="reported_at" value="<?= e((string) $repair['reported_at']) ?>" required></label>
            <label>Proveedor <input name="provider_name" value="<?= e((string) ($repair['provider_name'] ?? '')) ?>"></label>
            <label>Presupuesto <input type="number" step="0.01" name="budget" value="<?= e((string) ($repair['budget'] ?? '')) ?>"></label>
            <label>Costo <input type="number" step="0.01" name="cost" value="<?= e((string) ($repair['cost'] ?? '')) ?>"></label>
            <label>Estado
                <select name="status">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($repair['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($repair['space_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Problema <textarea name="problem_description"><?= e((string) ($repair['problem_description'] ?? '')) ?></textarea></label>
        <label>Notas <textarea name="notes"><?= e((string) ($repair['notes'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/repairs')) ?>">Cancelar</a>
        </div>
    </form>
</section>
