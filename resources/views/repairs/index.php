<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $repairs */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Reparaciones</h1></div></div>
<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nueva reparación</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Reportada <input type="date" name="reported_at" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Proveedor <input name="provider_name"></label>
            <label>Presupuesto <input type="number" step="0.01" name="budget"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Problema <textarea name="problem_description"></textarea></label>
        <button class="btn" type="submit">Registrar</button>
    </form>
</section>
<?php endif; ?>
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
