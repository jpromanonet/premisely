<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $routines */
/** @var list<array<string,mixed>> $spaces */
/** @var list<array<string,mixed>> $members */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Limpieza</h1><p>Rutinas con categoría cleaning.</p></div></div>
<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nueva rutina de limpieza</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/routines')) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="category" value="cleaning">
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach (['daily','weekly','monthly','yearly'] as $f): ?><option value="<?= $f ?>"><?= $f ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input type="number" name="frequency_interval" value="1" min="1"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Asignado
                <select name="assignee_member_id"><option value="">—</option>
                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['display_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Espacio</th><th>Próxima</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($routines as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e((string)($r['space_name'] ?? '—')) ?></td>
                    <td><?= e((string)$r['next_due_at']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/cleaning/' . $r['public_id'] . '/execute')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect" value="<?= e('/properties/' . $pid . '/cleaning') ?>">
                                <button class="btn btn--sm" type="submit">Hecho</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
