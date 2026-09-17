<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $plans */
/** @var list<array<string,mixed>> $records */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Mantenimiento</h1>
        <p>Cuida hoy, disfrutá siempre.</p>
    </div>
    <?php if (!empty($canEdit)): ?>
    <div class="actions">
        <a class="btn" href="<?= e(url('/properties/' . $pid . '/maintenance/plans/create')) ?>">+ Nuevo plan</a>
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/maintenance/records/create')) ?>">+ Registrar</a>
    </div>
    <?php endif; ?>
</div>
<section class="panel">
    <h2>Planes</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Próxima</th><th>Última</th><th>Proveedor</th><th data-nosort></th></tr></thead>
            <tbody>
            <?php if ($plans === []): ?>
                <tr><td colspan="5" class="muted">No hay planes todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e((string) ($p['next_due_at'] ?? '—')) ?></td>
                    <td><?= e((string) ($p['last_done_at'] ?? '—')) ?></td>
                    <td><?= e((string) ($p['provider_name'] ?? '—')) ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <div class="actions" style="margin-top:0">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/maintenance/plans/' . $p['public_id'] . '/edit')) ?>">Editar</a>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/maintenance/plans/' . $p['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este plan de mantenimiento?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Registros recientes</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Fecha</th><th>Título</th><th>Costo</th><th data-nosort></th></tr></thead>
            <tbody>
            <?php if ($records === []): ?>
                <tr><td colspan="4" class="muted">No hay registros todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($records as $r): ?>
                <tr>
                    <td><?= e((string) $r['performed_at']) ?></td>
                    <td><?= e($r['title']) ?></td>
                    <td>
                        <?php if ($r['cost'] !== null): ?>
                            <?= e((string) $r['cost']) ?> <?= e((string) ($r['currency'] ?? '')) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/maintenance/records/' . $r['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este registro?');">
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
