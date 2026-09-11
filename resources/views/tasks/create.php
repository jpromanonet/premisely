<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva tarea</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">← Volver</a>
</div>
<section class="panel">
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
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/tasks')) ?>">Cancelar</a>
        </div>
    </form>
</section>
