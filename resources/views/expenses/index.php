<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $expenses */
/** @var list<array<string,mixed>> $categories */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Gastos</h1></div></div>
<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nuevo gasto</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Monto <input type="number" step="0.01" name="amount" required></label>
            <label>Fecha <input type="date" name="spent_at" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Moneda <input name="currency" maxlength="3" value="<?= e((string)($property['currency'] ?? 'ARS')) ?>"></label>
            <label>Categoría
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <button class="btn" type="submit">Registrar</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Fecha</th><th>Título</th><th>Categoría</th><th>Monto</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $e): ?>
                <tr>
                    <td><?= e($e['spent_at']) ?></td>
                    <td><?= e($e['title']) ?></td>
                    <td><?= e((string)($e['category_name'] ?? '—')) ?></td>
                    <td><?= e((string)$e['amount']) ?> <?= e($e['currency']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses/' . $e['public_id'] . '/archive')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Archivar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
