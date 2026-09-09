<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $routines */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Lavandería</h1><p>Ropa diaria, sábanas, toallas y ciclos recurrentes.</p></div>
</div>
<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h2>Nueva tarea</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/laundry')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required placeholder="Lavar sábanas"></label>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach (['weekly','daily','monthly'] as $f): ?><option value="<?= e($f) ?>"><?= e($f) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input name="frequency_interval" value="1"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>
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
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/laundry/' . $r['id'] . '/execute')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn--sm" type="submit">Hecho</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$routines): ?><div class="empty">Sin tareas de lavandería.</div><?php endif; ?>
</section>
