<?php
/** @var array<string,mixed> $property */
/** @var string $day */
/** @var string $today */
/** @var string $month */
/** @var list<list<array{date:string,inMonth:bool,count:int}>> $weeks */
/** @var string $prevMonth */
/** @var string $nextMonth */
/** @var string $prevDay */
/** @var string $nextDay */
/** @var list<array<string,mixed>> $tasks */
/** @var list<array<string,mixed>> $undated */
/** @var list<array<string,mixed>> $repairTasks */
/** @var list<array<string,mixed>> $medicalTasks */
/** @var list<array<string,mixed>> $weekSpend */
/** @var float $weekSpendTotal */
/** @var list<array<string,mixed>> $habits */
/** @var list<array<string,mixed>> $meals */
/** @var list<array<string,mixed>> $repairs */
/** @var list<array<string,mixed>> $notes */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
/** @var bool $canEdit */
$medicalTasks = $medicalTasks ?? [];
$weekSpend = $weekSpend ?? [];
$weekSpendTotal = (float) ($weekSpendTotal ?? 0);
$spaces = $spaces ?? [];
$members = $members ?? [];
$priorities = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];

$pid = (string) $property['public_id'];
$back = '/properties/' . $pid . '/calendar?day=' . rawurlencode($day) . '&month=' . rawurlencode($month);
$months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$weekdays = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
$selected = new DateTimeImmutable($day);
$heading = ucfirst($weekdays[(int) $selected->format('N')]) . ' ' . $selected->format('j') . ' de ' . $months[(int) $selected->format('n')];
$monthLabel = ucfirst($months[(int) substr($month, 5, 2)]) . ' ' . substr($month, 0, 4);
$slots = [
    'desayuno' => 'Desayuno',
    'almuerzo' => 'Almuerzo',
    'merienda' => 'Merienda',
    'cena' => 'Cena',
];
$mealsBySlot = [];
foreach ($meals as $meal) {
    $mealsBySlot[(string) $meal['slot']][] = $meal;
}
$categories = [
    'general' => 'Casa',
    'cleaning' => 'Limpieza',
    'laundry' => 'Lavandería',
    'garden' => 'Jardín',
    'pets' => 'Mascotas',
    'other' => 'Otra',
];
$frequencies = ['daily' => 'cada día', 'weekly' => 'cada semana', 'monthly' => 'cada mes', 'yearly' => 'cada año'];
$priorities = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Día a día</p>
        <h1><?= e($heading) ?></h1>
    </div>
    <div class="actions" style="margin-top:0">
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/calendar', ['day' => $prevDay, 'month' => substr($prevDay, 0, 7)])) ?>">←</a>
        <?php if ($day !== $today): ?>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/calendar', ['day' => $today])) ?>">Hoy</a>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/calendar', ['day' => $nextDay, 'month' => substr($nextDay, 0, 7)])) ?>">→</a>
    </div>
</div>

<div class="day-board">
    <aside class="panel month-card">
        <div class="month-card__nav">
            <a href="<?= e(url('/properties/' . $pid . '/calendar', ['day' => $day, 'month' => $prevMonth])) ?>">←</a>
            <strong><?= e($monthLabel) ?></strong>
            <a href="<?= e(url('/properties/' . $pid . '/calendar', ['day' => $day, 'month' => $nextMonth])) ?>">→</a>
        </div>
        <div class="month-grid month-grid--head">
            <?php foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $label): ?><span><?= $label ?></span><?php endforeach; ?>
        </div>
        <?php foreach ($weeks as $week): ?>
            <div class="month-grid">
                <?php foreach ($week as $cell): ?>
                    <a class="month-day<?= $cell['date'] === $day ? ' is-selected' : '' ?><?= $cell['date'] === $today ? ' is-today' : '' ?><?= $cell['inMonth'] ? '' : ' is-out' ?>"
                       href="<?= e(url('/properties/' . $pid . '/calendar', ['day' => $cell['date'], 'month' => $month])) ?>">
                        <?= (int) substr($cell['date'], 8, 2) ?>
                        <?php if ($cell['count'] > 0): ?><i></i><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!empty($canEdit)): ?>
            <div class="month-card__adds">
                <button type="button" class="btn btn--block btn--task" data-modal-open="task-modal" aria-haspopup="dialog">+ Tarea</button>
                <button type="button" class="btn btn--block btn--repair" data-modal-open="repair-modal" aria-haspopup="dialog">+ Reparación</button>
                <button type="button" class="btn btn--block btn--medical" data-modal-open="medical-modal" aria-haspopup="dialog">+ Médico</button>
                <button type="button" class="btn btn--block btn--spend" data-modal-open="week-spend-modal" aria-haspopup="dialog">+ Gasto semana</button>
                <button type="button" class="btn btn--block btn--note" data-modal-open="note-modal" aria-haspopup="dialog">+ Nota</button>
            </div>
        <?php endif; ?>
    </aside>

    <div class="day-main">
        <h2 class="section-label section-label--meals">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 3v7a2 2 0 0 0 2 2h1v9"/>
                <path d="M7 3v7"/>
                <path d="M10 3v7"/>
                <path d="M17 3v18"/>
                <path d="M17 8h2a2 2 0 0 0 0-4h-2"/>
            </svg>
            Comidas
        </h2>
        <div class="meal-row">
            <?php foreach ($slots as $slot => $label): ?>
                <article class="meal-card meal-card--<?= e($slot) ?>">
                    <header><?= e($label) ?></header>
                    <?php foreach ($mealsBySlot[$slot] ?? [] as $meal): ?>
                        <div class="meal-item">
                            <strong><?= e((string) $meal['title']) ?></strong>
                            <?php if (!empty($canEdit)): ?>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/meals/' . $meal['public_id'] . '/delete')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button type="submit" title="Quitar">×</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!empty($canEdit)): ?>
                        <form class="meal-add" method="post" action="<?= e(url('/properties/' . $pid . '/meals')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="meal_date" value="<?= e($day) ?>">
                            <input type="hidden" name="slot" value="<?= e($slot) ?>">
                            <input type="hidden" name="week_start" value="<?= e($day) ?>">
                            <input type="hidden" name="redirect" value="<?= e($back) ?>">
                            <input name="title" placeholder="Agregar" required>
                            <button type="submit">+</button>
                        </form>
                    <?php elseif (($mealsBySlot[$slot] ?? []) === []): ?>
                        <p class="muted">Sin definir</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <section class="day-cut task-cut">
        <div class="section-head">
            <h2 class="section-label">Tareas pendientes</h2>
        </div>
        <div class="stack-cards quest-list">
            <?php if ($tasks === [] && ($day !== $today || $undated === [])): ?>
                <p class="empty-note">Nada pendiente este día.</p>
            <?php endif; ?>
            <?php foreach ($tasks as $task): ?>
                <?php
                $done = ($task['status'] ?? '') === 'done';
                $taskId = (string) $task['public_id'];
                $taskMeta = ['Tarea'];
                if (!empty($task['assignee_name'])) {
                    $taskMeta[] = (string) $task['assignee_name'];
                }
                if (!empty($task['priority']) && $task['priority'] !== 'normal') {
                    $taskMeta[] = $priorities[$task['priority']] ?? (string) $task['priority'];
                }
                ?>
                <article class="quest<?= $done ? ' is-done' : '' ?>" id="task-<?= e($taskId) ?>">
                    <?php if (!empty($canEdit)): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/status')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= $done ? 'pending' : 'done' ?>">
                            <input type="hidden" name="redirect" value="<?= e($back . '#task-' . $taskId) ?>">
                            <button class="quest-check<?= $done ? ' is-on' : '' ?>" type="submit" aria-label="<?= $done ? 'Desmarcar' : 'Hecho' ?>"><?= $done ? '✓' : '' ?></button>
                        </form>
                    <?php else: ?>
                        <span class="quest-check<?= $done ? ' is-on' : '' ?>"><?= $done ? '✓' : '' ?></span>
                    <?php endif; ?>
                    <div class="quest__body">
                        <strong><?= e((string) $task['title']) ?></strong>
                        <p><?= e(implode(' · ', $taskMeta)) ?></p>
                    </div>
                    <?php if (!empty($canEdit)): ?>
                        <div class="quest-tools">
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta tarea?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                <button class="quest-delete" type="submit" aria-label="Eliminar" title="Eliminar">×</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($day === $today): ?>
                <?php foreach ($undated as $task): ?>
                    <?php $taskId = (string) $task['public_id']; ?>
                    <article class="quest" id="task-<?= e($taskId) ?>">
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/status')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="done">
                                <input type="hidden" name="redirect" value="<?= e($back . '#task-' . $taskId) ?>">
                                <button class="quest-check" type="submit" aria-label="Hecho"></button>
                            </form>
                        <?php else: ?>
                            <span class="quest-check"></span>
                        <?php endif; ?>
                        <div class="quest__body">
                            <strong><?= e((string) $task['title']) ?></strong>
                            <p>Sin fecha<?php if (!empty($task['assignee_name'])): ?> · <?= e((string) $task['assignee_name']) ?><?php endif; ?></p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="quest-tools">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta tarea?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="quest-delete" type="submit" aria-label="Eliminar" title="Eliminar">×</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        </section>

        <section class="day-cut repair-cut">
            <div class="section-head">
                <h2 class="section-label">Reparaciones</h2>
            </div>
            <div class="stack-cards quest-list">
                <?php if ($repairTasks === [] && $repairs === []): ?>
                    <p class="empty-note">Nada para arreglar.</p>
                <?php endif; ?>
                <?php foreach ($repairTasks as $task): ?>
                    <?php
                    $taskId = (string) $task['public_id'];
                    $done = ($task['status'] ?? '') === 'done';
                    ?>
                    <article class="quest item-card--repair<?= $done ? ' is-done' : '' ?>" id="repair-<?= e($taskId) ?>">
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/status')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="<?= $done ? 'pending' : 'done' ?>">
                                <input type="hidden" name="redirect" value="<?= e($back . '#repair-' . $taskId) ?>">
                                <button class="quest-check<?= $done ? ' is-on' : '' ?>" type="submit" aria-label="<?= $done ? 'Desmarcar' : 'Hecho' ?>"><?= $done ? '✓' : '' ?></button>
                            </form>
                        <?php else: ?>
                            <span class="quest-check<?= $done ? ' is-on' : '' ?>"><?= $done ? '✓' : '' ?></span>
                        <?php endif; ?>
                        <div class="quest__body">
                            <strong><?= e((string) $task['title']) ?></strong>
                            <p>Reparación<?php if (!empty($task['assignee_name'])): ?> · <?= e((string) $task['assignee_name']) ?><?php endif; ?></p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="quest-tools">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta reparación?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="quest-delete" type="submit" aria-label="Eliminar" title="Eliminar">×</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php foreach ($repairs as $repair): ?>
                    <?php
                    $repairId = (string) $repair['public_id'];
                    $done = in_array((string) ($repair['status'] ?? ''), ['hecho', 'done'], true);
                    ?>
                    <article class="quest item-card--repair<?= $done ? ' is-done' : '' ?>" id="repair-<?= e($repairId) ?>">
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $repairId . '/close')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect" value="<?= e($back . '#repair-' . $repairId) ?>">
                                <button class="quest-check<?= $done ? ' is-on' : '' ?>" type="submit" aria-label="<?= $done ? 'Desmarcar' : 'Hecho' ?>"><?= $done ? '✓' : '' ?></button>
                            </form>
                        <?php else: ?>
                            <span class="quest-check<?= $done ? ' is-on' : '' ?>"><?= $done ? '✓' : '' ?></span>
                        <?php endif; ?>
                        <div class="quest__body">
                            <strong><?= e((string) $repair['title']) ?></strong>
                            <p>Reparación</p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="quest-tools">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $repairId . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta reparación?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="quest-delete" type="submit" aria-label="Eliminar" title="Eliminar">×</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="day-cut medical-cut">
            <div class="section-head">
                <h2 class="section-label">Temas médicos</h2>
            </div>
            <div class="stack-cards quest-list">
                <?php if ($medicalTasks === []): ?>
                    <p class="empty-note">Nada médico pendiente.</p>
                <?php endif; ?>
                <?php foreach ($medicalTasks as $task): ?>
                    <?php
                    $taskId = (string) $task['public_id'];
                    $done = ($task['status'] ?? '') === 'done';
                    ?>
                    <article class="quest item-card--medical<?= $done ? ' is-done' : '' ?>" id="medical-<?= e($taskId) ?>">
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/status')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="<?= $done ? 'pending' : 'done' ?>">
                                <input type="hidden" name="redirect" value="<?= e($back . '#medical-' . $taskId) ?>">
                                <button class="quest-check<?= $done ? ' is-on' : '' ?>" type="submit" aria-label="<?= $done ? 'Desmarcar' : 'Hecho' ?>"><?= $done ? '✓' : '' ?></button>
                            </form>
                        <?php else: ?>
                            <span class="quest-check<?= $done ? ' is-on' : '' ?>"><?= $done ? '✓' : '' ?></span>
                        <?php endif; ?>
                        <div class="quest__body">
                            <strong><?= e((string) $task['title']) ?></strong>
                            <p>Médico<?php if (!empty($task['assignee_name'])): ?> · <?= e((string) $task['assignee_name']) ?><?php endif; ?></p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="quest-tools">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $taskId . '/archive')) ?>" onsubmit="return confirm('¿Eliminar este tema?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="quest-delete" type="submit" aria-label="Eliminar" title="Eliminar">×</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="day-cut spend-cut">
            <div class="section-head">
                <h2 class="section-label">Gastos de la semana</h2>
                <strong class="spend-total"><?= e(format_money($weekSpendTotal)) ?></strong>
            </div>
            <div class="stack-cards quest-list">
                <?php if ($weekSpend === []): ?>
                    <p class="empty-note">Nada planificado para gastar esta semana.</p>
                <?php endif; ?>
                <?php foreach ($weekSpend as $item): ?>
                    <?php $spendDone = !empty($item['completed_at']); $spendId = (string) $item['public_id']; ?>
                    <article class="quest item-card--spend<?= $spendDone ? ' is-done' : '' ?>" id="spend-<?= e($spendId) ?>">
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/week-spend/' . $spendId . '/toggle')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect" value="<?= e($back . '#spend-' . $spendId) ?>">
                                <button class="quest-check<?= $spendDone ? ' is-on' : '' ?>" type="submit" aria-label="<?= $spendDone ? 'Desmarcar' : 'Hecho' ?>"><?= $spendDone ? '✓' : '' ?></button>
                            </form>
                        <?php else: ?>
                            <span class="quest-check<?= $spendDone ? ' is-on' : '' ?>"><?= $spendDone ? '✓' : '' ?></span>
                        <?php endif; ?>
                        <div class="quest__body">
                            <strong><?= e((string) $item['title']) ?></strong>
                            <p><?= e(format_money($item['amount'])) ?> <?= e((string) ($item['currency'] ?? 'ARS')) ?></p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="quest-tools">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/week-spend/' . $spendId . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este gasto?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="quest-delete" type="submit" aria-label="Eliminar" title="Eliminar">×</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="day-cut habit-cut">
            <div class="section-head">
                <h2 class="section-label">Hábitos</h2>
            </div>
            <?php
            $items = $habits;
            $canToggle = !empty($canEdit) && $day === $today;
            $emptyNote = $day === $today
                ? 'Nada para marcar en este día.'
                : 'Los hábitos solo se ven el día de hoy.';
            require view_path('routines/_quests.php');
            ?>
        </section>

        <section class="day-cut note-cut">
            <div class="section-head">
                <h2 class="section-label">Notas</h2>
            </div>
            <div class="stack-cards quest-list">
                <?php if ($notes === []): ?><p class="empty-note">Sin notas.</p><?php endif; ?>
                <?php foreach ($notes as $note): ?>
                    <?php $noteDone = !empty($note['completed_at']); $noteId = (string) $note['public_id']; ?>
                    <article class="quest<?= $noteDone ? ' is-done' : '' ?>" id="note-<?= e($noteId) ?>">
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/notes/' . $noteId . '/toggle')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect" value="<?= e($back . '#note-' . $noteId) ?>">
                                <button class="quest-check<?= $noteDone ? ' is-on' : '' ?>" type="submit" aria-label="<?= $noteDone ? 'Desmarcar' : 'Hecho' ?>"><?= $noteDone ? '✓' : '' ?></button>
                            </form>
                        <?php else: ?>
                            <span class="quest-check<?= $noteDone ? ' is-on' : '' ?>"><?= $noteDone ? '✓' : '' ?></span>
                        <?php endif; ?>
                        <div class="quest__body">
                            <strong><?= e((string) $note['title']) ?></strong>
                            <?php if (!empty($note['notes'])): ?><p><?= e((string) $note['notes']) ?></p><?php endif; ?>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="quest-tools">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/notes/' . $noteId . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta nota?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="quest-delete" type="submit" aria-label="Eliminar" title="Eliminar">×</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php if (!empty($canEdit)): ?>
<?php
$taskModals = [
    ['id' => 'task-modal', 'kind' => 'tarea', 'heading' => 'Nueva tarea'],
    ['id' => 'repair-modal', 'kind' => 'reparacion', 'heading' => 'Nueva reparación'],
    ['id' => 'medical-modal', 'kind' => 'medico', 'heading' => 'Nuevo tema médico'],
];
foreach ($taskModals as $tm):
?>
<div class="modal" id="<?= e($tm['id']) ?>" hidden>
    <button type="button" class="modal__backdrop" data-modal-close aria-label="Cerrar"></button>
    <div class="modal__panel modal__panel--wide" role="dialog" aria-modal="true" aria-labelledby="<?= e($tm['id']) ?>-title">
        <div class="modal__head">
            <h2 id="<?= e($tm['id']) ?>-title"><?= e($tm['heading']) ?></h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">×</button>
        </div>
        <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks')) ?>" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="kind" value="<?= e($tm['kind']) ?>">
            <input type="hidden" name="day" value="<?= e($day) ?>">
            <input type="hidden" name="redirect" value="<?= e($back) ?>">
            <div class="form-grid">
                <label>Título <input name="title" required></label>
                <label>Prioridad
                    <select name="priority">
                        <?php foreach ($priorities as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $value === 'normal' ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Espacio
                    <select name="space_id"><option value="">—</option>
                        <?php foreach ($spaces as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e((string) $s['name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Asignado
                    <select name="assignee_member_id"><option value="">—</option>
                        <?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e((string) $m['display_name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Descripción <textarea name="description" rows="3"></textarea></label>
            <div class="actions">
                <button class="btn" type="submit">Crear</button>
                <button class="btn btn--ghost" type="button" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<div class="modal" id="week-spend-modal" hidden>
    <button type="button" class="modal__backdrop" data-modal-close aria-label="Cerrar"></button>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="week-spend-title">
        <div class="modal__head">
            <h2 id="week-spend-title">Gasto de la semana</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">×</button>
        </div>
        <p class="muted">Algo en lo que hay que gastar esta semana.</p>
        <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/week-spend')) ?>" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="day" value="<?= e($day) ?>">
            <input type="hidden" name="redirect" value="<?= e($back) ?>">
            <div class="form-grid">
                <label>En qué hay que gastar <input name="title" required placeholder="Ej. supermercado, nafta"></label>
                <label>Precio <input type="number" name="amount" min="0.01" step="0.01" required></label>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Agregar</button>
                <button class="btn btn--ghost" type="button" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="note-modal" hidden>
    <button type="button" class="modal__backdrop" data-modal-close aria-label="Cerrar"></button>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="note-modal-title">
        <div class="modal__head">
            <h2 id="note-modal-title">Nueva nota</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">×</button>
        </div>
        <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/notes')) ?>" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= e($back) ?>">
            <input type="hidden" name="note_date" value="<?= e($day) ?>">
            <div class="form-grid">
                <label>Título <input name="title" required></label>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Agregar nota</button>
                <button class="btn btn--ghost" type="button" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
