<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $bills */
/** @var bool $canEdit */
$pid = $property['public_id'];
$statuses = [
    'active' => 'Activo',
    'paused' => 'Pausado',
    'cancelled' => 'Cancelado',
];
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
    <?php if ($services === []): ?>
        <p class="muted">No hay servicios todavía.</p>
    <?php endif; ?>
    <?php foreach ($services as $s): ?>
        <?php if (!empty($canEdit)): ?>
            <form id="service-status-<?= e($s['public_id']) ?>" method="post" action="<?= e(url('/properties/' . $pid . '/services/' . $s['public_id'] . '/status')) ?>" class="sr-only" aria-hidden="true">
                <?= csrf_field() ?>
            </form>
        <?php endif; ?>
        <article style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--line)">
            <div class="actions" style="margin-top:0;justify-content:space-between">
                <div>
                    <strong><?= e($s['name']) ?></strong>
                    <span class="muted"><?= e((string) ($s['provider'] ?? '')) ?> · <?= e((string) ($s['customer_number'] ?? '')) ?></span>
                </div>
                <?php if (!empty($canEdit)): ?>
                    <select class="select-compact" form="service-status-<?= e($s['public_id']) ?>" name="status" data-auto-submit>
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($s['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <span class="badge"><?= e($statuses[$s['status']] ?? (string) $s['status']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($canEdit)): ?>
                <div class="actions">
                    <a class="btn btn--sm" href="<?= e(url('/properties/' . $pid . '/services/' . $s['public_id'] . '/bills/create')) ?>">+ Agregar factura</a>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/services/' . $s['public_id'] . '/edit')) ?>">Editar</a>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/services/' . $s['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este servicio?');">
                        <?= csrf_field() ?>
                        <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                    </form>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<section class="panel">
    <h2>Facturas recientes</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Servicio</th><th>Período</th><th>Monto</th><th data-nosort></th></tr></thead>
            <tbody>
            <?php if ($bills === []): ?>
                <tr><td colspan="4" class="muted">No hay facturas todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($bills as $b): ?>
                <tr>
                    <td><?= e($b['service_name']) ?></td>
                    <td><?= e((string) ($b['period'] ?? '—')) ?></td>
                    <td><?= e((string) $b['amount']) ?> <?= e((string) ($b['currency'] ?? '')) ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/services/bills/' . $b['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta factura?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
