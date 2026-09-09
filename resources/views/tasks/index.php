<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $tasks */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Tareas</h1></div></div>
<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nueva tarea</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Prioridad
                <select name="priority">
                    <?php foreach (['low','normal','high','urgent'] as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Vence <input type="date" name="due_date"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Asignado
                <select name="assignee_member_id"><option value="">—</option>
                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['display_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Tags <input name="tags"></label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Estado</th><th>Prioridad</th><th>Vence</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?= e($t['title']) ?><div class="muted"><?= e((string)($t['assignee_name'] ?? '')) ?></div></td>
                    <td><?= e($t['status']) ?></td>
                    <td><?= e($t['priority']) ?></td>
                    <td><?= e((string)$t['due_date']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $t['public_id'] . '/status')) ?>" class="actions">
                                <?= csrf_field() ?>
                                <select name="status">
                                    <?php foreach (['pending','in_progress','done','cancelled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn--sm" type="submit">OK</button>
                            </form>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $t['public_id'] . '/archive')) ?>">
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
