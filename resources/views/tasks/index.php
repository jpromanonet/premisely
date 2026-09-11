<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $tasks */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Tareas</h1></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/tasks/create')) ?>">+ Nueva tarea</a><?php endif; ?>
</div>
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
