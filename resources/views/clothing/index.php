<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $items */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Ropa y pertenencias</h1><p>Prendas por integrante, temporada y ubicación.</p></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/clothing/create')) ?>">+ Nueva prenda</a><?php endif; ?>
</div>
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
