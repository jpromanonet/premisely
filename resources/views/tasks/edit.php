<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $task */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
$pid = $property['public_id'];
$priorities = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
$statuses = ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'done' => 'Hecho', 'cancelled' => 'Cancelada'];
?>
<div class="page-header">
    <div><h1>Editar tarea</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $task['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" value="<?= e((string) $task['title']) ?>" required></label>
            <label>Estado
                <select name="status">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($task['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridad
                <select name="priority">
                    <?php foreach ($priorities as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($task['priority'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Vence <input type="date" name="due_date" value="<?= e((string) ($task['due_date'] ?? '')) ?>"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($task['space_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Asignado
                <select name="assignee_member_id"><option value="">—</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= (int) ($task['assignee_member_id'] ?? 0) === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tags <input name="tags" value="<?= e((string) ($task['tags'] ?? '')) ?>"></label>
        </div>
        <label>Descripción <textarea name="description"><?= e((string) ($task['description'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">Cancelar</a>
        </div>
    </form>
</section>
