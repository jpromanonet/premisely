<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $categories */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Stock</h1><p>Consumibles y cantidades.</p></div></div>

<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nuevo ítem</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/stock')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Cantidad <input type="number" step="0.001" name="quantity" value="0"></label>
            <label>Unidad <input name="unit" value="u"></label>
            <label>Mínimo <input type="number" step="0.001" name="minimum_quantity" value="0"></label>
            <label>Objetivo <input type="number" step="0.001" name="target_quantity"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Categoría
                <select name="category_id"><option value="">—</option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><span>Auto agregar a compras</span> <input type="checkbox" name="auto_add_to_shopping" value="1"></label>
        </div>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Cantidad</th><th>Mínimo</th><th>Espacio</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td class="<?= (float)$item['quantity'] <= (float)$item['minimum_quantity'] ? 'low' : '' ?>">
                        <?= e((string)$item['quantity']) ?> <?= e($item['unit']) ?>
                    </td>
                    <td><?= e((string)$item['minimum_quantity']) ?></td>
                    <td><?= e((string)($item['space_name'] ?? '—')) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <details>
                                <summary>Ajustar / editar</summary>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/stock/' . $item['public_id'] . '/adjust')) ?>" class="actions" style="margin:.5rem 0">
                                    <?= csrf_field() ?>
                                    <select name="type">
                                        <?php foreach (['purchase','consume','adjustment','discard','expired'] as $t): ?>
                                            <option value="<?= $t ?>"><?= $t ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" step="0.001" name="quantity" value="1" required>
                                    <input type="number" step="0.01" name="unit_price" placeholder="Precio">
                                    <button class="btn btn--sm" type="submit">Registrar</button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/stock/' . $item['public_id'])) ?>" class="stack">
                                    <?= csrf_field() ?>
                                    <input name="name" value="<?= e($item['name']) ?>" required>
                                    <input name="unit" value="<?= e($item['unit']) ?>">
                                    <input type="number" step="0.001" name="minimum_quantity" value="<?= e((string)$item['minimum_quantity']) ?>">
                                    <input type="number" step="0.001" name="target_quantity" value="<?= e((string)$item['target_quantity']) ?>">
                                    <label><input type="checkbox" name="auto_add_to_shopping" value="1" <?= !empty($item['auto_add_to_shopping']) ? 'checked' : '' ?>> Auto compras</label>
                                    <button class="btn btn--sm" type="submit">Guardar</button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/stock/' . $item['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Archivar?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm" type="submit">Archivar</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
