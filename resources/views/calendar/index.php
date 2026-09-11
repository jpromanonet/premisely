<?php
/** @var array<string,mixed> $property */
/** @var string $month */
/** @var list<array{date:string,events:list<array<string,mixed>>}> $days */
/** @var string $prev */
/** @var string $next */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Calendario</h1><p>Tareas, rutinas, servicios, mantenimiento y notas.</p></div>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/calendar', ['month' => $prev])) ?>">←</a>
        <span class="mono"><?= e($month) ?></span>
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/calendar', ['month' => $next])) ?>">→</a>
        <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/calendar/notes/create')) ?>">+ Nueva nota</a><?php endif; ?>
    </div>
</div>
<div class="grid">
<?php foreach ($days as $day): ?>
    <?php if ($day['events'] === []) continue; ?>
    <section class="panel" style="margin:0">
        <h3 class="mono"><?= e($day['date']) ?></h3>
        <ul style="list-style:none;padding:0;margin:0">
        <?php foreach ($day['events'] as $ev): ?>
            <li style="padding:.4rem 0;border-bottom:1px solid var(--line)">
                <span class="badge badge-info"><?= e($ev['type']) ?></span>
                <?= e($ev['title']) ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
</div>
<?php
$hasAny = false;
foreach ($days as $day) { if ($day['events'] !== []) { $hasAny = true; break; } }
?>
<?php if (!$hasAny): ?><div class="empty">Sin eventos este mes.</div><?php endif; ?>
