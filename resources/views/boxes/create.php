<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva caja</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/boxes')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/boxes')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Código <input name="code" placeholder="C12"></label>
            <label>Nombre <input name="name" required></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/boxes')) ?>">Cancelar</a>
        </div>
    </form>
</section>
