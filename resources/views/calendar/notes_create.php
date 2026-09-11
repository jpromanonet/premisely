<?php
/** @var array<string,mixed> $property */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva nota</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/calendar')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/notes')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Fecha <input type="date" name="note_date" value="<?= e(date('Y-m-d')) ?>"></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Agregar nota</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/calendar')) ?>">Cancelar</a>
        </div>
    </form>
</section>
