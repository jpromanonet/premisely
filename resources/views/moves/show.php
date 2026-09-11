<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $move */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $inventory */
/** @var list<array<string,mixed>> $boxes */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
$done = ($move['status'] ?? '') === 'completed';
?>
<div class="page-header">
    <div>
        <h1><?= e($move['name']) ?></h1>
        <p>Estado: <?= e($move['status']) ?><?php if (!empty($move['completed_at'])): ?> · Aplicada <?= e($move['completed_at']) ?><?php endif; ?></p>
    </div>
    <div class="actions">
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/moves')) ?>">← Volver</a>
        <?php if (!empty($canEdit) && !$done): ?>
        <form method="post" action="<?= e(url('/properties/' . $pid . '/moves/' . $move['public_id'] . '/apply')) ?>" onsubmit="return confirm('¿Aplicar mudanza? Esta acción modifica el inventario.');">
            <?= csrf_field() ?>
            <button class="btn" type="submit">Aplicar mudanza</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($canEdit) && !$done): ?>
<section class="panel">
    <h2>Agregar objeto</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/moves/' . $move['public_id'] . '/items')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Objeto
                <select name="inventory_item_id" required>
                    <option value="">Elegí...</option>
                    <?php foreach ($inventory as $i): ?>
                        <option value="<?= (int) $i['id'] ?>"><?= e($i['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Disposición
                <select name="disposition">
                    <?php foreach (['transfer' => 'Trasladar', 'sell' => 'Vender', 'donate' => 'Donar', 'discard' => 'Descartar', 'leave' => 'Dejar'] as $k => $l): ?>
                        <option value="<?= e($k) ?>"><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Caja
                <select name="box_id">
                    <option value="">—</option>
                    <?php foreach ($boxes as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"><?= e($b['code'] . ' · ' . $b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Espacio destino
                <select name="target_space_id">
                    <option value="">—</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Agregar</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Objetos en la mudanza</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Objeto</th><th>Disposición</th><th>Caja</th><th>Espacio</th><th>Aplicado</th></tr></thead>
            <tbody>
            <?php if ($items === []): ?>
                <tr><td colspan="5" class="muted">Todavía no hay objetos.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['item_name']) ?></td>
                    <td><?= e($it['disposition']) ?></td>
                    <td class="mono"><?= e($it['box_code'] ?? '—') ?></td>
                    <td><?= e($it['space_name'] ?? '—') ?></td>
                    <td class="mono"><?= e($it['applied_at'] ?? 'pendiente') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
