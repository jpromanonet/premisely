<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $routines */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Lavandería</h1><p>Ropa diaria, sábanas, toallas y ciclos recurrentes.</p></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/laundry/create')) ?>">+ Nueva tarea</a><?php endif; ?>
</div>
<section class="panel">
    <table>
        <thead><tr><th>Tarea</th><th>Última</th><th>Próxima</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($routines as $r): ?>
            <tr>
                <td><?= e($r['title']) ?><div class="muted"><?= e($r['space_name'] ?? '') ?></div></td>
                <td class="mono"><?= e($r['last_executed_at'] ?? 'nunca') ?></td>
                <td class="mono"><?= e($r['next_due_at'] ?? '—') ?></td>
                <td>
                    <?php if (!empty($canEdit)): ?>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/laundry/' . $r['id'] . '/execute')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn--sm" type="submit">Hecho</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$routines): ?><div class="empty">Sin tareas de lavandería.</div><?php endif; ?>
</section>
