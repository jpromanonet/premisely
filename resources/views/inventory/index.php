<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $items */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Inventario</h1><p>Bienes y objetos de la propiedad.</p></div>
    <?php if ($canEdit): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/inventory/create')) ?>">Nuevo ítem</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Espacio</th><th>Categoría</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><a href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'])) ?>"><?= e($item['name']) ?></a></td>
                    <td><?= e((string) ($item['space_name'] ?? '—')) ?></td>
                    <td><?= e((string) ($item['category_name'] ?? '—')) ?></td>
                    <td><?= e($item['status']) ?></td>
                    <td><a href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'])) ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
