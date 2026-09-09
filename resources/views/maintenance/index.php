<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $plans */
/** @var list<array<string,mixed>> $records */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Mantenimiento</h1>
        <p>Cuida hoy, disfrutá siempre.</p>
    </div>
</div>
<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nuevo plan</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/maintenance/plans')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Frecuencia
                <select name="frequency_type">
                    <?php foreach (['weekly','monthly','yearly'] as $f): ?><option value="<?= $f ?>"><?= $f ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Intervalo <input type="number" name="frequency_interval" value="1" min="1"></label>
            <label>Proveedor <input name="provider_name"></label>
            <label>Costo estimado <input type="number" step="0.01" name="estimated_cost"></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <button class="btn" type="submit">Crear plan</button>
    </form>
</section>
<section class="panel">
    <h2>Registrar mantenimiento</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/maintenance/records')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Fecha <input type="date" name="performed_at" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Costo <input type="number" step="0.01" name="cost"></label>
            <label>Proveedor <input name="provider_name"></label>
            <label>Plan
                <select name="plan_id"><option value="">—</option>
                    <?php foreach ($plans as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><span>Crear gasto</span> <input type="checkbox" name="create_expense" value="1"></label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <button class="btn" type="submit">Guardar registro</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Planes</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Próxima</th><th>Última</th><th>Proveedor</th></tr></thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e((string)$p['next_due_at']) ?></td>
                    <td><?= e((string)$p['last_done_at']) ?></td>
                    <td><?= e((string)$p['provider_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Registros recientes</h2>
    <ul>
        <?php foreach ($records as $r): ?>
            <li><?= e($r['performed_at']) ?> · <?= e($r['title']) ?>
                <?php if ($r['cost'] !== null): ?> · <?= e((string)$r['cost']) ?> <?= e((string)$r['currency']) ?><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
