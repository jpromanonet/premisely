<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $moves */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Mudanzas</h1><p>Planificá traslados, ventas, donaciones y descartes.</p></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/moves/create')) ?>">+ Nueva mudanza</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Estado</th><th>Destino</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
            <?php if ($moves === []): ?>
                <tr><td colspan="5" class="muted">Sin mudanzas todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($moves as $m): ?>
                <tr>
                    <td><a href="<?= e(url('/properties/' . $pid . '/moves/' . $m['public_id'])) ?>"><?= e($m['name']) ?></a></td>
                    <td><?= e($m['status']) ?></td>
                    <td><?= e($m['destination_name'] ?? 'Misma') ?></td>
                    <td class="mono"><?= e($m['planned_at'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/moves/' . $m['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta mudanza?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
