<?php
/** @var array<string,mixed> $property */
/** @var string $weekStart */
/** @var string $weekEnd */
/** @var string $prevWeek */
/** @var string $nextWeek */
/** @var string $weekNumber */
/** @var string $weekYear */
/** @var string $rangeLabel */
/** @var bool $isCurrentWeek */
/** @var list<array<string,mixed>> $tasks */
/** @var list<array<string,mixed>> $repairs */
/** @var list<array<string,mixed>> $medical */
/** @var list<array<string,mixed>> $spend */
/** @var float $spendTotal */
/** @var list<array<string,mixed>> $habits */
/** @var int $habitsDoneCount */
/** @var list<array<string,mixed>> $notes */
$pid = $property['public_id'];
$currency = (string) ($property['currency'] ?? 'ARS');
$base = '/properties/' . $pid . '/weekly-report';
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Lunes a domingo</p>
        <h1>Informe semanal</h1>
        <p>Semana <?= e($weekNumber) ?> de <?= e($weekYear) ?> · <?= e($rangeLabel) ?><?php if (!empty($isCurrentWeek)): ?> · Esta semana<?php endif; ?></p>
    </div>
    <div class="actions">
        <a class="btn btn--ghost" href="<?= e(url($base, ['week' => $prevWeek])) ?>">← Anterior</a>
        <a class="btn btn--ghost" href="<?= e(url($base, ['week' => date('Y-m-d')])) ?>">Hoy</a>
        <a class="btn btn--ghost" href="<?= e(url($base, ['week' => $nextWeek])) ?>">Siguiente →</a>
        <a class="btn" href="<?= e(url($base . '/pdf', ['week' => $weekStart])) ?>">Exportar PDF</a>
    </div>
</div>

<div class="metric-grid metric-grid--weekly">
    <div class="metric">
        <span>Tareas</span>
        <strong><?= count($tasks) ?></strong>
    </div>
    <div class="metric">
        <span>Reparaciones</span>
        <strong><?= count($repairs) ?></strong>
    </div>
    <div class="metric">
        <span>Médicos</span>
        <strong><?= count($medical) ?></strong>
    </div>
    <div class="metric">
        <span>Hábitos hechos</span>
        <strong><?= (int) $habitsDoneCount ?></strong>
    </div>
</div>

<section class="panel week-report-cut week-report-cut--task">
    <h2>Tareas hechas</h2>
    <?php if ($tasks === []): ?>
        <p class="empty-note">Ninguna esta semana.</p>
    <?php else: ?>
        <ul class="week-report-list">
            <?php foreach ($tasks as $row): ?>
                <li>
                    <strong><?= e((string) $row['title']) ?></strong>
                    <?php $when = format_when($row['completed_at'] ?? ''); if ($when !== ''): ?>
                        <span class="muted"><?= e($when) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel week-report-cut week-report-cut--repair">
    <h2>Reparaciones hechas</h2>
    <?php if ($repairs === []): ?>
        <p class="empty-note">Ninguna esta semana.</p>
    <?php else: ?>
        <ul class="week-report-list">
            <?php foreach ($repairs as $row): ?>
                <li>
                    <strong><?= e((string) $row['title']) ?></strong>
                    <?php $when = format_when($row['completed_at'] ?? ''); if ($when !== ''): ?>
                        <span class="muted"><?= e($when) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel week-report-cut week-report-cut--medical">
    <h2>Temas médicos resueltos</h2>
    <?php if ($medical === []): ?>
        <p class="empty-note">Ninguno esta semana.</p>
    <?php else: ?>
        <ul class="week-report-list">
            <?php foreach ($medical as $row): ?>
                <li>
                    <strong><?= e((string) $row['title']) ?></strong>
                    <?php $when = format_when($row['completed_at'] ?? ''); if ($when !== ''): ?>
                        <span class="muted"><?= e($when) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel week-report-cut week-report-cut--spend">
    <div class="section-head">
        <h2>Gastos de la semana</h2>
        <strong class="spend-total"><?= e(format_money($spendTotal)) ?> <?= e($currency) ?></strong>
    </div>
    <?php if ($spend === []): ?>
        <p class="empty-note">Nada planificado esta semana.</p>
    <?php else: ?>
        <ul class="week-report-list">
            <?php foreach ($spend as $row): ?>
                <?php $done = !empty($row['completed_at']); ?>
                <li class="<?= $done ? 'is-done' : '' ?>">
                    <strong><?= e((string) $row['title']) ?></strong>
                    <span class="muted">
                        <?= e(format_money($row['amount'])) ?> <?= e((string) ($row['currency'] ?? $currency)) ?>
                        <?php if ($done): ?> · Hecho<?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel week-report-cut week-report-cut--habit">
    <div class="section-head">
        <h2>Hábitos repetidos</h2>
        <strong><?= (int) $habitsDoneCount ?></strong>
    </div>
    <?php if ($habits === []): ?>
        <p class="empty-note">Sin hábitos marcados esta semana.</p>
    <?php else: ?>
        <ul class="week-report-list">
            <?php foreach ($habits as $row): ?>
                <li>
                    <strong><?= e((string) $row['title']) ?></strong>
                    <span class="muted">
                        <?= (int) $row['times'] ?> <?= (int) $row['times'] === 1 ? 'vez' : 'veces' ?>
                        <?php if ((int) ($row['streak'] ?? 0) > 0): ?>
                            · racha <?= (int) $row['streak'] ?>
                        <?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel week-report-cut week-report-cut--note">
    <h2>Notas leídas</h2>
    <?php if ($notes === []): ?>
        <p class="empty-note">Ninguna esta semana.</p>
    <?php else: ?>
        <ul class="week-report-list">
            <?php foreach ($notes as $row): ?>
                <li>
                    <strong><?= e((string) $row['title']) ?></strong>
                    <?php $when = format_when($row['completed_at'] ?? ''); if ($when !== ''): ?>
                        <span class="muted"><?= e($when) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
