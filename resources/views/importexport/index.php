<?php
/** @var array<string,mixed> $property */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Importar / Exportar</h1><p>Evitá el lock-in: CSV, JSON y ZIP de documentos.</p></div>
</div>
<section class="panel">
    <h2>Exportar</h2>
    <div class="actions">
        <a class="btn" href="<?= e(url('/properties/' . $pid . '/importexport/export/inventory', ['format' => 'csv'])) ?>">Inventario CSV</a>
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/importexport/export/inventory', ['format' => 'json'])) ?>">Inventario JSON</a>
        <a class="btn" href="<?= e(url('/properties/' . $pid . '/importexport/export/stock', ['format' => 'csv'])) ?>">Stock CSV</a>
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/importexport/export/stock', ['format' => 'json'])) ?>">Stock JSON</a>
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/importexport/export/documents-zip')) ?>">Documentos ZIP</a>
    </div>
</section>
<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h2>Importar inventario</h2>
    <p class="muted">CSV/JSON con columna <code>name</code> (opcional: brand, model).</p>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/importexport/import/inventory')) ?>" enctype="multipart/form-data" class="stack">
        <?= csrf_field() ?>
        <label>Archivo <input type="file" name="file" accept=".csv,.json,text/csv,application/json" required></label>
        <button class="btn" type="submit">Importar inventario</button>
    </form>
</section>
<section class="panel">
    <h2>Importar stock</h2>
    <p class="muted">CSV/JSON con <code>name</code>, <code>quantity</code>, <code>unit</code>.</p>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/importexport/import/stock')) ?>" enctype="multipart/form-data" class="stack">
        <?= csrf_field() ?>
        <label>Archivo <input type="file" name="file" accept=".csv,.json,text/csv,application/json" required></label>
        <button class="btn" type="submit">Importar stock</button>
    </form>
</section>
<?php endif; ?>
