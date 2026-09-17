<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva reparación</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/repairs')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Reportada <input type="date" name="reported_at" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Proveedor <input name="provider_name"></label>
            <label>Presupuesto <input type="number" step="0.01" name="budget"></label>
            <label>Estado
                <select name="status">
                    <?php foreach (($statuses ?? ['pendiente' => 'Pendiente', 'en_curso' => 'En curso', 'hecho' => 'Hecho', 'cancelada' => 'Cancelada']) as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $value === 'pendiente' ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Problema <textarea name="problem_description"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Registrar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/repairs')) ?>">Cancelar</a>
        </div>
    </form>
</section>
