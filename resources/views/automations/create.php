<?php
/** @var array<string,mixed> $property */
/** @var array<string,string> $available */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Nueva automatización</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/automations')) ?>">← Volver</a>
</div>
<section class="panel">
    <?php if ($available === []): ?>
        <p class="muted">Ya tenés todas las automatizaciones disponibles. Eliminá una de la lista si querés volver a agregarla.</p>
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/automations')) ?>">← Volver</a>
    <?php else: ?>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/automations')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Regla
            <select name="type" required>
                <option value="">Elegí…</option>
                <?php foreach ($available as $type => $label): ?>
                    <option value="<?= e($type) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <input type="checkbox" name="enabled" value="1" checked>
            Activar al crear
        </label>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/automations')) ?>">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
</section>
