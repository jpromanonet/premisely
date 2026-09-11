<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $destinations */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva mudanza</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/moves')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/moves')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required placeholder="Mudanza a..."></label>
            <label>Propiedad destino
                <select name="destination_property_id">
                    <option value="">Misma propiedad</option>
                    <?php foreach ($destinations as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Fecha planificada <input type="date" name="planned_at"></label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/moves')) ?>">Cancelar</a>
        </div>
    </form>
</section>
