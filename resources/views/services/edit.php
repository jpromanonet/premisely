<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $service */
$pid = $property['public_id'];
$statuses = ['active' => 'Activo', 'paused' => 'Pausado', 'cancelled' => 'Cancelado'];
$freqs = ['monthly' => 'Mensual', 'bimonthly' => 'Bimestral', 'quarterly' => 'Trimestral', 'yearly' => 'Anual'];
?>
<div class="page-header">
    <div><h1>Editar servicio</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/services')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/services/' . $service['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e((string) $service['name']) ?>" required></label>
            <label>Proveedor <input name="provider" value="<?= e((string) ($service['provider'] ?? '')) ?>"></label>
            <label>Nº cliente <input name="customer_number" value="<?= e((string) ($service['customer_number'] ?? '')) ?>"></label>
            <label>Frecuencia
                <select name="billing_frequency">
                    <?php foreach ($freqs as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($service['billing_frequency'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Monto típico <input type="number" step="0.01" name="typical_amount" value="<?= e((string) ($service['typical_amount'] ?? '')) ?>"></label>
            <label>Próximo vencimiento <input type="date" name="next_due_date" value="<?= e((string) ($service['next_due_date'] ?? '')) ?>"></label>
            <label>Estado
                <select name="status">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($service['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Notas <textarea name="notes"><?= e((string) ($service['notes'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/services')) ?>">Cancelar</a>
        </div>
    </form>
</section>
