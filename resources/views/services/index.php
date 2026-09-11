<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $bills */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Servicios</h1>
        <p>Luz, gas, internet y otros.</p>
    </div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/services/create')) ?>">+ Nuevo servicio</a><?php endif; ?>
</div>
<section class="panel">
    <h2>Servicios activos</h2>
    <?php foreach ($services as $s): ?>
        <article style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--line)">
            <strong><?= e($s['name']) ?></strong>
            <span class="muted"><?= e((string)$s['provider']) ?> · <?= e((string)$s['customer_number']) ?></span>
            <?php if ($canEdit): ?>
                <div style="margin-top:.5rem">
                    <a class="btn btn--sm" href="<?= e(url('/properties/' . $pid . '/services/' . $s['public_id'] . '/bills/create')) ?>">+ Agregar factura</a>
                </div>
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
