<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $items */
/** @var string $back */
/** @var bool $canToggle */
$pid = $property['public_id'];
$manage = !empty($manage);
$view = (string) ($view ?? '');
$category = (string) ($category ?? '');
$categories = [
    'general' => 'Casa',
    'cleaning' => 'Limpieza',
    'laundry' => 'Lavandería',
    'garden' => 'Jardín',
    'pets' => 'Mascotas',
    'other' => 'Otra',
];
$frequencies = [
    'daily:1' => 'Cada día',
    'daily:2' => 'Cada 2 días',
    'weekly:1' => 'Cada semana',
    'weekly:2' => 'Cada 2 semanas',
    'monthly:1' => 'Cada mes',
    'yearly:1' => 'Cada año',
];
$when = static function (array $habit) use ($frequencies): string {
    $key = (string) ($habit['frequency_type'] ?? 'weekly') . ':' . max(1, (int) ($habit['frequency_interval'] ?? 1));
    return $frequencies[$key] ?? 'Se repite';
};
?>
<div class="stack-cards quest-list">
    <?php if ($items === []): ?>
        <p class="empty-note"><?= e((string) ($emptyNote ?? 'Nada para marcar en este día.')) ?></p>
    <?php endif; ?>
    <?php foreach ($items as $index => $habit): ?>
        <?php
        $checked = !empty($habit['done_today']);
        $position = $index + 1;
        $freqKey = (string) ($habit['frequency_type'] ?? 'weekly') . ':' . max(1, (int) ($habit['frequency_interval'] ?? 1));
        if (!isset($frequencies[$freqKey])) {
            $freqKey = 'weekly:1';
        }
        ?>
        <article class="quest<?= $checked ? ' is-done' : '' ?><?= empty($habit['due_today']) ? ' is-later' : '' ?>">
            <?php if (!empty($canToggle) && !empty($habit['due_today'])): ?>
                <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $habit['public_id'] . ($checked ? '/undo' : '/execute'))) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                    <button class="quest-check" type="submit" aria-label="<?= $checked ? 'Desmarcar' : 'Hecho' ?>"><?= $checked ? '✓' : '' ?></button>
                </form>
            <?php else: ?>
                <span class="quest-check<?= $checked ? ' is-on' : '' ?>"><?= $checked ? '✓' : '' ?></span>
            <?php endif; ?>
            <?php if ($manage && !empty($canEdit)): ?>
                <form class="quest-order" method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $habit['public_id'] . '/reorder')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                    <input type="hidden" name="view" value="<?= e($view) ?>">
                    <input class="quest-pos" type="number" name="position" min="1" max="<?= count($items) ?>" value="<?= $position ?>" aria-label="Orden" onchange="this.form.submit()">
                    <button type="submit" name="direction" value="up" aria-label="Subir">↑</button>
                    <button type="submit" name="direction" value="down" aria-label="Bajar">↓</button>
                </form>
            <?php elseif ($manage): ?>
                <span class="quest-pos"><?= $position ?></span>
            <?php else: ?>
                <span class="quest-pos quest-pos--num"><?= (int) ($habit['list_position'] ?? $position) ?></span>
            <?php endif; ?>
            <?php
            $questNotes = [];
            if (!$manage) {
                $questNotes[] = $categories[$habit['category']] ?? (string) $habit['category'];
                $questNotes[] = $when($habit);
            }
            if (!empty($habit['assignee_name'])) {
                $questNotes[] = (string) $habit['assignee_name'];
            }
            $scheduleDay = (int) ($habit['schedule_day'] ?? 0);
            if (($habit['frequency_type'] ?? '') === 'weekly' && $scheduleDay >= 1 && $scheduleDay <= 7) {
                $questNotes[] = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'][$scheduleDay];
            } elseif (($habit['frequency_type'] ?? '') === 'monthly' && $scheduleDay >= 1 && $scheduleDay <= 31) {
                $questNotes[] = 'día ' . $scheduleDay;
            }
            if (empty($habit['due_today'])) {
                $questNotes[] = 'no toca hoy';
            }
            if (!empty($habit['overdue'])) {
                $questNotes[] = 'atrasado';
            }
            if ((int) ($habit['streak'] ?? 0) > 1) {
                $questNotes[] = 'racha ' . (int) $habit['streak'];
            }
            ?>
            <div class="quest__body">
                <strong><?= e((string) $habit['title']) ?></strong>
                <?php if ($questNotes !== []): ?><p><?= e(implode(' · ', $questNotes)) ?></p><?php endif; ?>
            </div>
            <?php if ($manage && !empty($canEdit)): ?>
                <div class="quest-tools">
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $habit['public_id'] . '/frequency')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= e($back) ?>">
                        <select class="quest-freq" name="frequency" aria-label="Frecuencia" onchange="this.form.submit()">
                            <?php foreach ($frequencies as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $freqKey === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $habit['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este hábito?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= e($back) ?>">
                        <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                    </form>
                </div>
            <?php elseif (!empty($canEdit)): ?>
                <div class="quest-tools">
                    <a class="quest-edit" href="<?= e(url('/properties/' . $pid . '/routines/' . $habit['public_id'] . '/edit')) ?>">Editar</a>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
