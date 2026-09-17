<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $provider */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Editar proveedor</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/providers')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/providers/' . $provider['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e((string) $provider['name']) ?>" required></label>
            <label>Especialidad <input name="specialty" value="<?= e((string) ($provider['specialty'] ?? '')) ?>"></label>
            <label>Teléfono <input name="phone" value="<?= e((string) ($provider['phone'] ?? '')) ?>"></label>
            <label>Email <input type="email" name="email" value="<?= e((string) ($provider['email'] ?? '')) ?>"></label>
            <label>Valoración (1-5) <input type="number" min="1" max="5" name="rating" value="<?= e((string) ($provider['rating'] ?? '')) ?>"></label>
        </div>
        <label>Notas <textarea name="notes"><?= e((string) ($provider['notes'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/providers')) ?>">Cancelar</a>
        </div>
    </form>
</section>
