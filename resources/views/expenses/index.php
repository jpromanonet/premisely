<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $expenses */
/** @var list<array<string,mixed>> $fixedExpenses */
/** @var array<string,string> $frequencies */
/** @var bool $canEdit */
$pid = $property['public_id'];
$fixedExpenses = $fixedExpenses ?? [];
$frequencies = $frequencies ?? [];
?>
<div class="page-header">
    <div>
        <h1>Gastos</h1>
        <p>Gastos fijos y movimientos puntuales.</p>
    </div>
    <?php if (!empty($canEdit)): ?>
        <div class="actions">
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/expenses/fixed/create')) ?>">+ Gasto fijo</a>
            <a class="btn" href="<?= e(url('/properties/' . $pid . '/expenses/create')) ?>">+ Nuevo gasto</a>
        </div>
    <?php endif; ?>
</div>

<section class="panel">
    <h2>Gastos fijos</h2>
    <?php if ($fixedExpenses === []): ?>
        <p class="muted">Todavía no hay gastos fijos.<?php if (!empty($canEdit)): ?> <a href="<?= e(url('/properties/' . $pid . '/expenses/fixed/create')) ?>">Creá el primero</a>.<?php endif; ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nombre</th><th>Categoría</th><th>Frecuencia</th><th>Monto</th><th>Próximo</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($fixedExpenses as $f): ?>
                    <tr>
                        <td><?= e($f['title']) ?></td>
                        <td><?= e((string) ($f['category_name'] ?? '—')) ?></td>
                        <td><?= e($frequencies[$f['frequency_type']] ?? (string) $f['frequency_type']) ?></td>
                        <td><?= e(format_money($f['amount'])) ?> <?= e($f['currency']) ?></td>
                        <td><?= e((string) ($f['next_due_date'] ?? '—')) ?></td>
                        <td><?= !empty($f['is_active']) ? 'Activo' : 'Pausado' ?></td>
                        <td class="actions" style="white-space:nowrap">
                            <?php if ($canEdit): ?>
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/expenses/fixed/' . $f['public_id'] . '/edit')) ?>">Editar</a>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses/fixed/' . $f['public_id'] . '/delete')) ?>" style="display:inline" onsubmit="return confirm('¿Eliminar este gasto fijo?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Movimientos</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Fecha</th><th>Título</th><th>Categoría</th><th>Monto</th><th></th></tr></thead>
            <tbody>
            <?php if ($expenses === []): ?>
                <tr><td colspan="5" class="muted">Sin gastos registrados.</td></tr>
            <?php endif; ?>
            <?php foreach ($expenses as $e): ?>
                <tr>
                    <td><?= e($e['spent_at']) ?></td>
                    <td><?= e($e['title']) ?></td>
                    <td><?= e((string) ($e['category_name'] ?? '—')) ?></td>
                    <td><?= e(format_money($e['amount'])) ?> <?= e($e['currency']) ?></td>
                    <td class="actions" style="white-space:nowrap">
                        <?php if ($canEdit): ?>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/expenses/' . $e['public_id'] . '/edit')) ?>">Editar</a>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses/' . $e['public_id'] . '/archive')) ?>" style="display:inline">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Archivar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
