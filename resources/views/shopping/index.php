<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $list */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $stockItems */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Lista de compras</h1><p><?= e($list['name']) ?></p></div></div>

<?php if ($canEdit): ?>
<section class="panel">
    <h2>Agregar ítem</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Cantidad <input type="number" step="0.001" name="quantity" value="1"></label>
            <label>Unidad <input name="unit" value="u"></label>
            <label>Precio estimado <input type="number" step="0.01" name="estimated_price"></label>
            <label>Stock relacionado
                <select name="stock_item_id">
                    <option value="">—</option>
                    <?php foreach ($stockItems as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridad
                <select name="priority">
                    <?php foreach (['low','normal','high'] as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Agregar</button>
    </form>
</section>
<?php endif; ?>

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
