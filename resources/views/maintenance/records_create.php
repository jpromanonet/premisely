<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $plans */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Registrar mantenimiento</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/maintenance/records')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Fecha <input type="date" name="performed_at" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Costo <input type="number" step="0.01" name="cost"></label>
            <label>Proveedor <input name="provider_name"></label>
            <label>Plan
                <select name="plan_id"><option value="">—</option>
                    <?php foreach ($plans as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><span>Crear gasto</span> <input type="checkbox" name="create_expense" value="1"></label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar registro</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/maintenance')) ?>">Cancelar</a>
        </div>
    </form>
</section>
