<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nueva rutina</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/routines')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/routines')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Categoría
                <select name="category">
                    <?php foreach (['general','cleaning','garden','pets','other'] as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach (['daily','weekly','monthly','yearly'] as $f): ?><option value="<?= $f ?>"><?= $f ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input type="number" name="frequency_interval" value="1" min="1"></label>
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
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Crear</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/routines')) ?>">Cancelar</a>
        </div>
    </form>
</section>
