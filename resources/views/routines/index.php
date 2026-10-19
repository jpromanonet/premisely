<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $paused */
/** @var int $done */
/** @var int $total */
/** @var string $view */
/** @var string $today */
/** @var bool $canEdit */
$pid = $property['public_id'];
$views = [
    'daily' => 'Diarios',
    'weekly' => 'Semanales',
    'monthly' => 'Mensuales',
];
$view = isset($views[$view]) ? $view : 'daily';
$pct = $total > 0 ? (int) round($done / $total * 100) : 0;
$back = url('/properties/' . $pid . '/routines', ['view' => $view]);
$weekdays = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
$heading = ucfirst($weekdays[(int) date('N')]) . ' ' . date('j');
$empty = [
    'daily' => 'No hay hábitos diarios.',
    'weekly' => 'No hay hábitos semanales.',
    'monthly' => 'No hay hábitos mensuales.',
];
$lead = [
    'daily' => 'Los de todos los días.',
    'weekly' => 'Los que vuelven cada semana.',
    'monthly' => 'Los que vuelven cada mes.',
];
?>
<div class="page-header">
    <div>
        <p class="eyebrow"><?= e($lead[$view]) ?></p>
        <h1>Hábitos</h1>
        <p><?= e($heading) ?> · <?= $total === 0 ? 'ninguno para hoy' : e((string) $done . ' de ' . $total . ' para hoy') ?></p>
    </div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/routines/create')) ?>">+ Hábito</a><?php endif; ?>
</div>
<div class="chip-row">
    <?php foreach ($views as $value => $label): ?>
        <a class="chip<?= $view === $value ? ' is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/routines', ['view' => $value])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<?php if ($total > 0): ?>
    <div class="quest-progress" aria-hidden="true"><span style="width: <?= $pct ?>%"></span></div>
<?php endif; ?>
<?php
$canToggle = !empty($canEdit);
$manage = true;
$emptyNote = $empty[$view];
require view_path('routines/_quests.php');
?>
<?php if ($paused !== []): ?>
    <h2 class="section-label" style="margin-top:22px">Pausados</h2>
    <div class="stack-cards">
        <?php foreach ($paused as $habit): ?>
            <article class="quest is-paused">
                <span class="quest-check" aria-hidden="true"></span>
                <div class="quest__body">
                    <strong><?= e((string) $habit['title']) ?></strong>
                </div>
                <?php if (!empty($canEdit)): ?>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $habit['public_id'] . '/toggle')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= e($back) ?>">
                        <button class="btn btn--ghost btn--sm" type="submit">Activar</button>
                    </form>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $habit['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este hábito?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= e($back) ?>">
                        <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
