<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $meal */
/** @var string $weekStart */
$pid = $property['public_id'];
$slots = ['desayuno', 'almuerzo', 'merienda', 'cena'];
?>
<div class="page-header">
    <div><h1>Editar comida</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/meals', ['week' => $weekStart])) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/meals/' . $meal['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="week_start" value="<?= e($weekStart) ?>">
        <div class="form-grid">
            <label>Fecha <input type="date" name="meal_date" value="<?= e((string) $meal['meal_date']) ?>" required></label>
            <label>Momento
                <select name="slot">
                    <?php foreach ($slots as $s): ?>
                        <option value="<?= e($s) ?>" <?= ($meal['slot'] ?? '') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Título <input name="title" value="<?= e((string) $meal['title']) ?>" required></label>
        </div>
        <label>Notas <textarea name="notes"><?= e((string) ($meal['notes'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/meals', ['week' => $weekStart])) ?>">Cancelar</a>
        </div>
    </form>
</section>
