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
/** @var list<array<string,mixed>> $habits */
/** @var list<array<string,mixed>> $meals */
/** @var list<array<string,mixed>> $repairs */
/** @var list<array<string,mixed>> $notes */
/** @var bool $canEdit */

$pid = (string) $property['public_id'];
$back = url('/properties/' . $pid . '/calendar', ['day' => $day, 'month' => $month]);
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
        <a class="btn btn--ghost btn--block" href="<?= e(url('/properties/' . $pid . '/routines')) ?>">Ver hábitos</a>
    </aside>

    <div class="day-main">
        <h2 class="section-label">Comidas</h2>
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

        <div class="section-head">
            <h2 class="section-label">Tareas del día</h2>
            <?php if (!empty($canEdit)): ?>
                <div class="actions section-actions">
                    <a class="btn btn--sm" href="<?= e(url('/properties/' . $pid . '/tasks/create', ['kind' => 'tarea'])) ?>">+ Tarea</a>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/tasks/create', ['kind' => 'reparacion'])) ?>">+ Reparación</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="stack-cards">
            <?php if ($tasks === [] && ($day !== $today || $undated === [])): ?>
                <p class="empty-note">Nada pendiente este día.</p>
            <?php endif; ?>
            <?php foreach ($tasks as $task): ?>
                <?php $done = ($task['status'] ?? '') === 'done'; ?>
                <article class="item-card<?= $done ? ' is-done' : '' ?>">
                    <div>
                        <strong><?= e((string) $task['title']) ?></strong>
                        <p class="muted">
                            Tarea
                            <?php if (!empty($task['assignee_name'])): ?> · <?= e((string) $task['assignee_name']) ?><?php endif; ?>
                            <?php if (!empty($task['priority']) && $task['priority'] !== 'normal'): ?> · <?= e($priorities[$task['priority']] ?? (string) $task['priority']) ?><?php endif; ?>
                            <?php if ($done): ?> · Hecha<?php endif; ?>
                        </p>
                    </div>
                    <?php if (!empty($canEdit)): ?>
                        <div class="actions" style="margin-top:0">
                            <?php if (!$done): ?>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $task['public_id'] . '/status')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="done">
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="btn btn--sm" type="submit">Hecho</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $task['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta tarea?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                <button class="btn btn--ghost btn--sm" type="submit">Eliminar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($day === $today): ?>
                <?php foreach ($undated as $task): ?>
                    <article class="item-card">
                        <div>
                            <strong><?= e((string) $task['title']) ?></strong>
                            <p class="muted">Sin fecha<?php if (!empty($task['assignee_name'])): ?> · <?= e((string) $task['assignee_name']) ?><?php endif; ?></p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="actions" style="margin-top:0">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $task['public_id'] . '/status')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="done">
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="btn btn--sm" type="submit">Hecho</button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $task['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta tarea?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="btn btn--ghost btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <section class="repair-cut">
            <div class="section-head">
                <h2 class="section-label">Reparaciones</h2>
            </div>
            <div class="stack-cards">
                <?php if ($repairTasks === [] && $repairs === []): ?>
                    <p class="empty-note">Nada para arreglar.</p>
                <?php endif; ?>
                <?php foreach ($repairTasks as $task): ?>
                    <article class="item-card item-card--repair">
                        <div>
                            <strong><?= e((string) $task['title']) ?></strong>
                            <p class="muted">Reparación<?php if (!empty($task['assignee_name'])): ?> · <?= e((string) $task['assignee_name']) ?><?php endif; ?></p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="actions" style="margin-top:0">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $task['public_id'] . '/status')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="done">
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="btn btn--sm" type="submit">Hecho</button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $task['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta tarea?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="btn btn--ghost btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php foreach ($repairs as $repair): ?>
                    <article class="item-card item-card--repair">
                        <div>
                            <strong><?= e((string) $repair['title']) ?></strong>
                            <p class="muted">Reparación</p>
                        </div>
                        <?php if (!empty($canEdit)): ?>
                            <div class="actions" style="margin-top:0">
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $repair['public_id'] . '/close')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="btn btn--sm" type="submit">Hecho</button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $repair['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta tarea?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                                    <button class="btn btn--ghost btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="section-head">
            <h2 class="section-label">Hábitos</h2>
            <?php if (!empty($canEdit)): ?>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/routines/create')) ?>">+ Hábito</a>
            <?php endif; ?>
        </div>
        <?php
        $items = $habits;
        $canToggle = !empty($canEdit) && $day === $today;
        require view_path('routines/_quests.php');
        ?>

        <div class="section-head">
            <h2 class="section-label">Notas</h2>
            <?php if (!empty($canEdit)): ?>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/calendar/notes/create')) ?>">+ Nota</a>
            <?php endif; ?>
        </div>
        <div class="stack-cards">
            <?php if ($notes === []): ?><p class="empty-note">Sin notas.</p><?php endif; ?>
            <?php foreach ($notes as $note): ?>
                <article class="item-card">
                    <div>
                        <strong><?= e((string) $note['title']) ?></strong>
                        <?php if (!empty($note['notes'])): ?><p class="muted"><?= e((string) $note['notes']) ?></p><?php endif; ?>
                    </div>
                    <?php if (!empty($canEdit)): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/calendar/notes/' . $note['public_id'] . '/delete')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="redirect" value="<?= e($back) ?>">
                            <button class="btn btn--ghost btn--sm" type="submit">Quitar</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>
