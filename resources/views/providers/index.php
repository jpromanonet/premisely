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
                        <div class="actions" style="margin-top:0">
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/providers/' . $p['public_id'] . '/edit')) ?>">Editar</a>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/providers/' . $p['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Eliminar este proveedor?');">
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
    <?php if (!$providers): ?><div class="empty">Sin proveedores todavía.</div><?php endif; ?>
</section>
