<?php /** @var list<array<string,mixed>> $properties */ ?>
<div class="page-header">
    <div>
        <h1>Propiedades</h1>
        <p>Gestioná casas, departamentos y otros espacios.</p>
    </div>
    <a class="btn" href="<?= e(url('/properties/create')) ?>">Nueva</a>
</div>
<section class="panel">
    <?php if ($properties === []): ?>
        <p class="muted">No hay propiedades todavía.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nombre</th><th>Tipo</th><th>Tenencia</th><th>Estado</th><th>Tu rol</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($properties as $p): ?>
                    <?php
                    $role = (string) ($p['role'] ?? 'viewer');
                    $canManage = in_array($role, ['owner', 'admin'], true);
                    $canEdit = in_array($role, ['owner', 'admin', 'member', 'collaborator'], true);
                    ?>
                    <tr>
                        <td><a href="<?= e(url('/properties/' . $p['public_id'] . '/dashboard')) ?>"><?= e($p['name']) ?></a></td>
                        <td><?= e(property_type_label($p['type'] ?? null)) ?></td>
                        <td><?= e(property_tenure_label($p['tenure'] ?? null)) ?></td>
                        <td><?= e(ucfirst((string) ($p['status'] ?? ''))) ?></td>
                        <td><?= e(member_role_label($p['role'] ?? null)) ?></td>
                        <td class="actions" style="white-space:nowrap">
                            <a href="<?= e(url('/properties/' . $p['public_id'] . '/dashboard')) ?>">Panel</a>
                            <?php if ($canEdit || $canManage): ?>
                                · <a href="<?= e(url('/properties/' . $p['public_id'] . '/edit')) ?>">Editar</a>
                            <?php endif; ?>
                            <?php if ($canManage): ?>
                                · <a class="text-danger" href="<?= e(url('/properties/' . $p['public_id'] . '/edit')) ?>#eliminar">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
