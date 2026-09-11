<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $list */
/** @var list<array<string,mixed>> $items */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Lista de compras</h1>
        <p><?= e($list['name']) ?></p>
    </div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/shopping/create')) ?>">+ Nuevo ítem</a><?php endif; ?>
</div>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ítem</th><th>Cantidad</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e((string)$item['quantity']) ?> <?= e($item['unit']) ?></td>
                    <td><span class="badge"><?= e($item['status']) ?></span></td>
                    <td>
                        <?php if ($canEdit && $item['status'] === 'pending'): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'] . '/complete')) ?>" class="actions">
                                <?= csrf_field() ?>
                                <input type="number" step="0.01" name="actual_price" placeholder="Precio real">
                                <label><input type="checkbox" name="update_stock" value="1"> Stock</label>
                                <label><input type="checkbox" name="create_expense" value="1"> Gasto</label>
                                <button class="btn btn--sm" type="submit">Comprado</button>
                            </form>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'] . '/delete')) ?>">
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
