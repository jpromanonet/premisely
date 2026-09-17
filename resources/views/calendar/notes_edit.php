<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $note */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Editar nota</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/calendar')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/notes/' . $note['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" value="<?= e((string) $note['title']) ?>" required></label>
            <label>Fecha <input type="date" name="note_date" value="<?= e((string) $note['note_date']) ?>"></label>
        </div>
        <label>Notas <textarea name="notes"><?= e((string) ($note['notes'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/calendar')) ?>">Cancelar</a>
        </div>
    </form>
</section>
