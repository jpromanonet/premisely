<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $tasks */
/** @var bool $canEdit */
$pid = $property['public_id'];
$statuses = [
    'pending' => 'Pendiente',
    'in_progress' => 'En curso',
    'done' => 'Hecho',
    'cancelled' => 'Cancelada',
];
$priorities = [
    'low' => 'Baja',
    'normal' => 'Normal',
    'high' => 'Alta',
    'urgent' => 'Urgente',
];
?>
<div class="page-header">
    <div><h1>Tareas</h1></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/tasks/create')) ?>">+ Nueva tarea</a><?php endif; ?>
</div>
<section class="panel">
    <?php foreach ($tasks as $t): ?>
        <?php if (!empty($canEdit)): ?>
            <form id="task-status-<?= e($t['public_id']) ?>" method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $t['public_id'] . '/status')) ?>" class="sr-only" aria-hidden="true">
                <?= csrf_field() ?>
            </form>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Estado</th><th>Prioridad</th><th>Vence</th><th data-nosort></th></tr></thead>
            <tbody>
            <?php if ($tasks === []): ?>
                <tr><td colspan="5" class="muted">No hay tareas todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?= e($t['title']) ?><div class="muted"><?= e((string) ($t['assignee_name'] ?? '')) ?></div></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <select class="select-compact" form="task-status-<?= e($t['public_id']) ?>" name="status" data-auto-submit>
                                <?php foreach ($statuses as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $t['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?= e($statuses[$t['status']] ?? $t['status']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($priorities[$t['priority']] ?? $t['priority']) ?></td>
                    <td><?= e((string) ($t['due_date'] ?? '—')) ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <div class="actions" style="margin-top:0">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/tasks/' . $t['public_id'] . '/edit')) ?>">Editar</a>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $t['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta tarea?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
