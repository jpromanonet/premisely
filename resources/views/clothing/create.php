<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $members */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva prenda</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/clothing')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/clothing')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Categoría
                <select name="category">
                    <?php foreach (['prenda','calzado','accesorio','abrigo','otro'] as $c): ?>
                        <option value="<?= e($c) ?>"><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Temporada <input name="season" placeholder="invierno"></label>
            <label>Talle <input name="size_label"></label>
            <label>Integrante
                <select name="owner_member_id"><option value="">—</option>
                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['display_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Ubicación
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/clothing')) ?>">Cancelar</a>
        </div>
    </form>
</section>
