<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
/** @var string $due */
/** @var string $kind */
$pid = $property['public_id'];
$back = url('/properties/' . $pid . '/calendar', ['day' => $due ?: date('Y-m-d')]);
$kinds = ['tarea' => 'Tarea', 'reparacion' => 'Reparación'];
$priorities = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
?>
<div class="page-header">
    <div>
        <h1><?= ($kind ?? 'tarea') === 'reparacion' ? 'Nueva reparación' : 'Nueva tarea' ?></h1>
    </div>
    <a class="btn btn--ghost" href="<?= e($back) ?>">← Volver al día</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks')) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect" value="<?= e($back) ?>">
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Tipo
                <select name="kind">
                    <?php foreach ($kinds as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($kind ?? 'tarea') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridad
                <select name="priority">
                    <?php foreach ($priorities as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $value === 'normal' ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Asignado
                <select name="assignee_member_id"><option value="">—</option>
                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e((string) $m['display_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e($back) ?>">Cancelar</a>
        </div>
    </form>
</section>
