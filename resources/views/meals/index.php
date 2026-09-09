<?php
/** @var array<string,mixed> $property */
/** @var string $weekStart */
/** @var list<string> $days */
/** @var array<string,list<array<string,mixed>>> $byDate */
/** @var bool $canEdit */
$pid = $property['public_id'];
$slots = ['desayuno','almuerzo','merienda','cena'];
$prev = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$next = date('Y-m-d', strtotime($weekStart . ' +7 days'));
?>
<div class="page-header">
    <div>
        <h1>Comidas</h1>
        <p>Plan semanal sin convertirse en una app de recetas.</p>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/meals', ['week' => $prev])) ?>">← Semana</a>
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/meals', ['week' => $next])) ?>">Semana →</a>
    </div>
</div>
<p class="muted">Semana del <span class="mono"><?= e($weekStart) ?></span></p>

<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h2>Agregar comida</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/meals')) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="week_start" value="<?= e($weekStart) ?>">
        <div class="form-grid">
            <label>Fecha <input type="date" name="meal_date" value="<?= e($weekStart) ?>" required></label>
            <label>Momento
                <select name="slot">
                    <?php foreach ($slots as $s): ?><option value="<?= e($s) ?>"><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Título <input name="title" required placeholder="Pollo al limón"></label>
        </div>
        <button class="btn" type="submit">Agregar</button>
    </form>
</section>
<?php endif; ?>

<div class="grid">
<?php foreach ($days as $day): ?>
    <section class="panel" style="margin:0">
        <h3 class="mono"><?= e($day) ?></h3>
        <?php if (empty($byDate[$day])): ?>
            <p class="muted">Sin comidas.</p>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0">
            <?php foreach ($byDate[$day] as $meal): ?>
                <li style="padding:.45rem 0;border-bottom:1px solid var(--line)">
                    <span class="badge badge-info"><?= e($meal['slot']) ?></span>
                    <?= e($meal['title']) ?>
                    <?php if (!empty($canEdit)): ?>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/meals/' . $meal['public_id'] . '/delete')) ?>" style="display:inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn--sm" type="submit">×</button>
                    </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
</div>
