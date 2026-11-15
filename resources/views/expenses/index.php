<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $fixedExpenses */
/** @var array<string,string> $frequencies */
/** @var bool $canEdit */
$pid = $property['public_id'];
$fixedExpenses = $fixedExpenses ?? [];
$frequencies = $frequencies ?? [];
?>
<div class="page-header">
    <div>
        <h1>Gastos fijos</h1>
        <p>Lo que se paga siempre: alquiler, servicios, cuotas.</p>
    </div>
    <?php if (!empty($canEdit)): ?>
        <div class="actions">
            <a class="btn" href="<?= e(url('/properties/' . $pid . '/expenses/fixed/create')) ?>">+ Nuevo</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($fixedExpenses === []): ?>
    <p class="empty-note">Todavía no hay gastos fijos.<?php if (!empty($canEdit)): ?> <a href="<?= e(url('/properties/' . $pid . '/expenses/fixed/create')) ?>">Creá el primero</a>.<?php endif; ?></p>
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
                    <td class="row-actions">
                        <?php if ($canEdit): ?>
                            <div class="row-actions__group">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/expenses/fixed/' . $f['public_id'] . '/edit')) ?>">Editar</a>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses/fixed/' . $f['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este gasto fijo?');">
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
<?php endif; ?>
