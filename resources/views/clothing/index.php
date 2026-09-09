<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $members */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Ropa y pertenencias</h1><p>Prendas por integrante, temporada y ubicación.</p></div>
</div>
<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h2>Nueva prenda</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/clothing')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Categoría
                <select name="category">
                    <?php foreach (['prenda','calzado','accesorio','abrigo','otro'] as $c): ?>
                        <option value="<?= e($c) ?>"><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Temporada <input name="season" placeholder="invierno"></label>
            <label>Talle <input name="size_label"></label>
            <label>Integrante
                <select name="owner_member_id"><option value="">—</option>
                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['display_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Ubicación
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Guardar</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <table>
        <thead><tr><th>Prenda</th><th>Dueño</th><th>Temporada</th><th>Ubicación</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
            <tr>
                <td><?= e($i['name']) ?> <span class="badge"><?= e($i['category']) ?></span></td>
                <td><?= e($i['owner_name'] ?? '—') ?></td>
                <td><?= e($i['season'] ?? '—') ?></td>
                <td><?= e($i['space_name'] ?? '—') ?></td>
                <td><span class="badge badge-ok"><?= e($i['status']) ?></span></td>
                <td>
                    <?php if (!empty($canEdit)): ?>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/clothing/' . $i['public_id'] . '/archive')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn--sm" type="submit">Archivar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$items): ?><div class="empty">Sin prendas registradas.</div><?php endif; ?>
</section>
