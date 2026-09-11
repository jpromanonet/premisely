<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva tarea</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/laundry')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/laundry')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required placeholder="Lavar sábanas"></label>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach (['weekly','daily','monthly'] as $f): ?><option value="<?= e($f) ?>"><?= e($f) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input name="frequency_interval" value="1"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/laundry')) ?>">Cancelar</a>
        </div>
    </form>
</section>
