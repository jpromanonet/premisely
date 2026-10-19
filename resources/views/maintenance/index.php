<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $jobs */
/** @var string $filter */
/** @var int $pending */
/** @var int $done */
/** @var bool $canEdit */
$pid = $property['public_id'];
$filters = [
    '' => 'Todos',
    'pendiente' => 'Pendientes',
    'hecho' => 'Hechos',
];
$back = url('/properties/' . $pid . '/maintenance', $filter === '' ? [] : ['estado' => $filter]);
$empty = [
    '' => 'No hay trabajos cargados.',
    'pendiente' => 'No hay pendientes.',
    'hecho' => 'No hay hechos.',
];
?>
<div class="page-header">
    <div>
        <h1>Mantenimiento</h1>
        <p><?= (int) $pending ?> pendientes · <?= (int) $done ?> hechos</p>
    </div>
</div>

<div class="chip-row">
    <?php foreach ($filters as $value => $label): ?>
        <a class="chip<?= $filter === $value ? ' is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/maintenance', $value === '' ? [] : ['estado' => $value])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if (!empty($canEdit)): ?>
    <form class="job-add" method="post" action="<?= e(url('/properties/' . $pid . '/tasks')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="kind" value="reparacion">
        <input type="hidden" name="priority" value="normal">
        <input type="hidden" name="redirect" value="<?= e('/properties/' . $pid . '/maintenance') ?>">
        <input name="title" required placeholder="Qué hay que hacer en casa" aria-label="Trabajo">
        <button class="btn" type="submit">Agregar</button>
    </form>
<?php endif; ?>

<?php if ($jobs === []): ?>
    <p class="empty-note"><?= e($empty[$filter] ?? $empty['']) ?></p>
<?php endif; ?>

<div class="stack-cards">
    <?php foreach ($jobs as $job): ?>
        <?php $isDone = ($job['status'] ?? '') === 'done'; ?>
        <article class="quest<?= $isDone ? ' is-done' : '' ?>">
            <?php if (!empty($canEdit)): ?>
                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $job['public_id'] . '/status')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="<?= $isDone ? 'pending' : 'done' ?>">
                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                    <button class="quest-check" type="submit" aria-label="<?= $isDone ? 'Marcar pendiente' : 'Marcar hecho' ?>"><?= $isDone ? '✓' : '' ?></button>
                </form>
            <?php else: ?>
                <span class="quest-check<?= $isDone ? ' is-on' : '' ?>"><?= $isDone ? '✓' : '' ?></span>
            <?php endif; ?>
            <div class="quest__body">
                <strong><?= e((string) $job['title']) ?></strong>
                <p><?= $isDone ? 'Hecho' : 'Pendiente' ?></p>
            </div>
            <?php if (!empty($canEdit)): ?>
                <form method="post" action="<?= e(url('/properties/' . $pid . '/tasks/' . $job['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Sacar este trabajo?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e($back) ?>">
                    <button class="btn btn--ghost btn--sm" type="submit">Sacar</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
