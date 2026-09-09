<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $bills */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Servicios</h1><p>Luz, gas, internet y otros.</p></div></div>
<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nuevo servicio</h2>
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
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Servicios activos</h2>
    <?php foreach ($services as $s): ?>
        <article style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--line)">
            <strong><?= e($s['name']) ?></strong>
            <span class="muted"><?= e((string)$s['provider']) ?> · <?= e((string)$s['customer_number']) ?></span>
            <?php if ($canEdit): ?>
                <form method="post" action="<?= e(url('/properties/' . $pid . '/services/' . $s['public_id'] . '/bills')) ?>" class="form-grid" style="margin-top:.75rem">
                    <?= csrf_field() ?>
                    <label>Período <input name="period" placeholder="2026-09"></label>
                    <label>Monto <input type="number" step="0.01" name="amount" required></label>
                    <label>Vence <input type="date" name="due_date"></label>
                    <label>Pagado <input type="date" name="paid_at"></label>
                    <label><span>Crear gasto (utilities)</span> <input type="checkbox" name="create_expense" value="1" checked></label>
                    <div><button class="btn btn--sm" type="submit">Agregar factura</button></div>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<section class="panel">
    <h2>Facturas recientes</h2>
    <ul>
        <?php foreach ($bills as $b): ?>
            <li><?= e($b['service_name']) ?> · <?= e((string)$b['period']) ?> · <?= e((string)$b['amount']) ?> <?= e((string)$b['currency']) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
