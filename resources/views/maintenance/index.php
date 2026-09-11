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
            <thead><tr><th>Título</th><th>Próxima</th><th>Última</th><th>Proveedor</th></tr></thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e((string)$p['next_due_at']) ?></td>
                    <td><?= e((string)$p['last_done_at']) ?></td>
                    <td><?= e((string)$p['provider_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Registros recientes</h2>
    <ul>
        <?php foreach ($records as $r): ?>
            <li><?= e($r['performed_at']) ?> · <?= e($r['title']) ?>
                <?php if ($r['cost'] !== null): ?> · <?= e((string)$r['cost']) ?> <?= e((string)$r['currency']) ?><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
