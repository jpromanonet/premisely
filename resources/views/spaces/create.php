<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nuevo espacio</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/spaces')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/spaces')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Tipo
                <select name="type">
                    <?php foreach (['ambiente','habitacion','baño','cocina','garage','jardin','deposito','otro'] as $t): ?>
                        <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Padre
                <select name="parent_id">
                    <option value="">— Ninguno —</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/spaces')) ?>">Cancelar</a>
        </div>
    </form>
</section>
