<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $routine */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
/** @var string $listPath */
/** @var string $formAction */
/** @var string $heading */
$pid = $property['public_id'];
$listPath = $listPath ?? ('/properties/' . $pid . '/routines');
$formAction = $formAction ?? ('/properties/' . $pid . '/routines/' . $routine['public_id']);
$heading = $heading ?? 'Editar rutina';
$freqs = ['daily' => 'Diaria', 'weekly' => 'Semanal', 'monthly' => 'Mensual', 'yearly' => 'Anual'];
$categories = ['general', 'cleaning', 'laundry', 'garden', 'pets', 'other'];
$fixedCategory = $fixedCategory ?? null;
?>
<div class="page-header">
    <div><h1><?= e($heading) ?></h1></div>
    <a class="btn btn--ghost" href="<?= e(url($listPath)) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url($formAction)) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" value="<?= e((string) $routine['title']) ?>" required></label>
            <?php if ($fixedCategory): ?>
                <input type="hidden" name="category" value="<?= e($fixedCategory) ?>">
            <?php else: ?>
                <label>Categoría
                    <select name="category">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= e($c) ?>" <?= ($routine['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach ($freqs as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($routine['frequency_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input type="number" name="frequency_interval" value="<?= e((string) ($routine['frequency_interval'] ?? 1)) ?>" min="1"></label>
            <label>Próxima <input type="datetime-local" name="next_due_at" value="<?= e($routine['next_due_at'] ? date('Y-m-d\TH:i', strtotime((string) $routine['next_due_at'])) : '') ?>"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($routine['space_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if (!empty($members)): ?>
            <label>Asignado
                <select name="assignee_member_id"><option value="">—</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= (int) ($routine['assignee_member_id'] ?? 0) === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
            <label>Estado
                <select name="is_active">
                    <option value="1" <?= (int) ($routine['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Activa</option>
                    <option value="0" <?= (int) ($routine['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Pausada</option>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"><?= e((string) ($routine['description'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url($listPath)) ?>">Cancelar</a>
        </div>
    </form>
</section>
