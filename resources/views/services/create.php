<?php
/** @var array<string,mixed> $property */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nuevo servicio</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/services')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/services')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Proveedor <input name="provider"></label>
            <label>Nº cliente <input name="customer_number"></label>
            <label>Frecuencia
                <select name="billing_frequency">
                    <?php foreach (['monthly','bimonthly','quarterly','yearly'] as $f): ?><option value="<?= $f ?>"><?= $f ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Monto típico <input type="number" step="0.01" name="typical_amount"></label>
            <label>Próximo vencimiento <input type="date" name="next_due_date"></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/services')) ?>">Cancelar</a>
        </div>
    </form>
</section>
