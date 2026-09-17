<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $plan */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
$freqs = ['weekly' => 'Semanal', 'monthly' => 'Mensual', 'yearly' => 'Anual'];
?>
<div class="page-header">
    <div><h1>Editar plan</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/maintenance/plans/' . $plan['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" value="<?= e((string) $plan['title']) ?>" required></label>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach ($freqs as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($plan['frequency_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input type="number" name="frequency_interval" value="<?= e((string) ($plan['frequency_interval'] ?? 1)) ?>" min="1"></label>
            <label>Proveedor <input name="provider_name" value="<?= e((string) ($plan['provider_name'] ?? '')) ?>"></label>
            <label>Costo estimado <input type="number" step="0.01" name="estimated_cost" value="<?= e((string) ($plan['estimated_cost'] ?? '')) ?>"></label>
            <label>Próxima <input type="date" name="next_due_at" value="<?= e((string) ($plan['next_due_at'] ?? '')) ?>"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($plan['space_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"><?= e((string) ($plan['description'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>">Cancelar</a>
        </div>
    </form>
</section>
