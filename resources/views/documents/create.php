<?php
/** @var array<string,mixed> $property */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Subir documento</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/documents')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/documents')) ?>" enctype="multipart/form-data" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Categoría
                <select name="category">
                    <?php foreach (['contrato','garantia','factura','plano','foto','otro'] as $c): ?>
                        <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Archivo <input type="file" name="file" required></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Subir</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/documents')) ?>">Cancelar</a>
        </div>
    </form>
</section>
