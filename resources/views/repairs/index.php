<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $repairs */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Reparaciones</h1></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/repairs/create')) ?>">+ Nueva reparación</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Estado</th><th>Reportada</th><th>Espacio</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($repairs as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td><span class="badge"><?= e($r['status']) ?></span></td>
                    <td><?= e($r['reported_at']) ?></td>
                    <td><?= e((string)($r['space_name'] ?? '—')) ?></td>
                    <td>
                        <?php if ($canEdit && $r['status'] !== 'closed'): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $r['public_id'])) ?>" class="actions">
                                <?= csrf_field() ?>
                                <select name="status">
                                    <?php foreach (['open','in_progress','closed'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn--sm" type="submit">Actualizar</button>
                            </form>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $r['public_id'] . '/close')) ?>" class="actions" style="margin-top:.35rem">
                                <?= csrf_field() ?>
                                <input type="number" step="0.01" name="cost" placeholder="Costo">
                                <label><input type="checkbox" name="create_expense" value="1"> Gasto</label>
                                <button class="btn btn--sm" type="submit">Cerrar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
