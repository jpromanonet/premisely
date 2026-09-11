<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $providers */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Proveedores</h1><p>Libreta de contactos de la propiedad.</p></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/providers/create')) ?>">+ Nuevo proveedor</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Especialidad</th><th>Teléfono</th><th>Email</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($providers as $p): ?>
                <tr>
                    <td><?= e($p['name']) ?><?php if ($p['rating']): ?> <span class="badge badge-info"><?= (int)$p['rating'] ?>★</span><?php endif; ?></td>
                    <td><?= e($p['specialty'] ?? '—') ?></td>
                    <td class="mono"><?= e($p['phone'] ?? '—') ?></td>
                    <td><?= e($p['email'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/providers/' . $p['public_id'] . '/archive')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger btn--sm" data-confirm="¿Archivar?" type="submit">Archivar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$providers): ?><div class="empty">Sin proveedores todavía.</div><?php endif; ?>
</section>
