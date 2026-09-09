<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $rows */
/** @var int $days */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Análisis de consumo</h1><p>Consumo aproximado según movimientos de stock.</p></div>
    <div class="actions">
        <a class="btn <?= $days === 30 ? '' : 'btn--ghost' ?>" href="<?= e(url('/properties/' . $pid . '/consumption', ['days' => 30])) ?>">30 días</a>
        <a class="btn <?= $days === 90 ? '' : 'btn--ghost' ?>" href="<?= e(url('/properties/' . $pid . '/consumption', ['days' => 90])) ?>">90 días</a>
    </div>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Producto</th><th>Stock</th><th>Consumido</th><th>Prom./día</th><th>Días a mínimo</th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="muted">Sin datos de consumo en el período.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['name']) ?></td>
                    <td class="mono"><?= e($r['quantity'] . ' ' . $r['unit']) ?></td>
                    <td class="mono"><?= e($r['consumed']) ?></td>
                    <td class="mono"><?= e((string) $r['daily_avg']) ?></td>
                    <td class="mono"><?= $r['days_to_min'] !== null ? (int) $r['days_to_min'] : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
