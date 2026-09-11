<?php
/** @var array<string,mixed> $property */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nuevo proveedor</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/providers')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/providers')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Especialidad <input name="specialty" placeholder="plomero, gasista..."></label>
            <label>Teléfono <input name="phone"></label>
            <label>Email <input type="email" name="email"></label>
            <label>Valoración (1-5) <input type="number" min="1" max="5" name="rating"></label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Agregar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/providers')) ?>">Cancelar</a>
        </div>
    </form>
</section>
