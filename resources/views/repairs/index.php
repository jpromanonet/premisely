<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $repairs */
/** @var array<string,string> $statuses */
/** @var bool $canEdit */
$pid = $property['public_id'];
$statuses = $statuses ?? [
    'pendiente' => 'Pendiente',
    'en_curso' => 'En curso',
    'hecho' => 'Hecho',
    'cancelada' => 'Cancelada',
];
?>
<div class="page-header">
    <div><h1>Reparaciones</h1></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/repairs/create')) ?>">+ Nueva reparación</a><?php endif; ?>
</div>
<section class="panel">
    <?php foreach ($repairs as $r): ?>
        <?php if (!empty($canEdit)): ?>
            <form id="repair-status-<?= e($r['public_id']) ?>" method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $r['public_id'])) ?>" class="sr-only" aria-hidden="true">
                <?= csrf_field() ?>
            </form>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Estado</th><th>Reportada</th><th>Espacio</th><th data-nosort></th></tr></thead>
            <tbody>
            <?php if ($repairs === []): ?>
                <tr><td colspan="5" class="muted">No hay reparaciones todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($repairs as $r): ?>
                <?php
                $statusKey = (string) ($r['status'] ?? 'pendiente');
                $statusLabel = $statuses[$statusKey] ?? $statusKey;
                $badgeClass = match ($statusKey) {
                    'hecho' => 'badge-ok',
                    'en_curso' => 'badge-info',
                    'cancelada' => 'badge-neutral',
                    default => 'badge-warn',
                };
                ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <select class="select-compact" form="repair-status-<?= e($r['public_id']) ?>" name="status" data-auto-submit>
                                <?php foreach ($statuses as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $statusKey === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <span class="badge <?= e($badgeClass) ?>"><?= e($statusLabel) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($r['reported_at']) ?></td>
                    <td><?= e((string) ($r['space_name'] ?? '—')) ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <div class="actions" style="margin-top:0">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/repairs/' . $r['public_id'] . '/edit')) ?>">Editar</a>
                                <?php if ($statusKey !== 'hecho'): ?>
                                    <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $r['public_id'] . '/close')) ?>" class="actions" style="margin-top:0">
                                        <?= csrf_field() ?>
                                        <input class="select-compact" type="number" step="0.01" name="cost" placeholder="Costo">
                                        <label class="checkbox-inline"><input type="checkbox" name="create_expense" value="1"> Gasto</label>
                                        <button class="btn btn--sm" type="submit">Marcar hecho</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/repairs/' . $r['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta reparación?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
