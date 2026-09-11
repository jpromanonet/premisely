<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nuevo plan</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/maintenance/plans')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach (['weekly','monthly','yearly'] as $f): ?><option value="<?= $f ?>"><?= $f ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input type="number" name="frequency_interval" value="1" min="1"></label>
            <label>Proveedor <input name="provider_name"></label>
            <label>Costo estimado <input type="number" step="0.01" name="estimated_cost"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Crear plan</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>">Cancelar</a>
        </div>
    </form>
</section>
