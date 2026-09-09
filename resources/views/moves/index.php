<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $moves */
/** @var list<array<string,mixed>> $destinations */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Mudanzas</h1><p>Planificá traslados, ventas, donaciones y descartes.</p></div>
</div>
<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h2>Nueva mudanza</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/moves')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required placeholder="Mudanza a..."></label>
            <label>Propiedad destino
                <select name="destination_property_id">
                    <option value="">Misma propiedad</option>
                    <?php foreach ($destinations as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Fecha planificada <input type="date" name="planned_at"></label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Estado</th><th>Destino</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
            <?php if ($moves === []): ?>
                <tr><td colspan="5" class="muted">Sin mudanzas todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($moves as $m): ?>
                <tr>
                    <td><a href="<?= e(url('/properties/' . $pid . '/moves/' . $m['public_id'])) ?>"><?= e($m['name']) ?></a></td>
                    <td><?= e($m['status']) ?></td>
                    <td><?= e($m['destination_name'] ?? 'Misma') ?></td>
                    <td class="mono"><?= e($m['planned_at'] ?? '—') ?></td>
                    <td><a href="<?= e(url('/properties/' . $pid . '/moves/' . $m['public_id'])) ?>">Abrir</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
