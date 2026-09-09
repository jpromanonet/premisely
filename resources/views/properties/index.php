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
                <thead><tr><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Rol</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($properties as $p): ?>
                    <tr>
                        <td><a href="<?= e(url('/properties/' . $p['public_id'])) ?>"><?= e($p['name']) ?></a></td>
                        <td><?= e($p['type']) ?></td>
                        <td><?= e($p['status']) ?></td>
                        <td><?= e($p['role']) ?></td>
                        <td><a href="<?= e(url('/properties/' . $p['public_id'] . '/dashboard')) ?>">Panel</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
