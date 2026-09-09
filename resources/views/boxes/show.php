<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $box */
/** @var list<array<string,mixed>> $items */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1><?= e($box['name']) ?> <span class="mono muted"><?= e($box['code']) ?></span></h1>
        <p><?= e($box['space_name'] ?? 'Sin ubicación') ?><?php if ($box['description']): ?> · <?= e($box['description']) ?><?php endif; ?></p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/boxes')) ?>">Volver</a>
</div>
<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h2>Agregar contenido</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/boxes/' . $box['public_id'] . '/items')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Cantidad <input name="quantity" value="1"></label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <button class="btn" type="submit">Agregar</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <table>
        <thead><tr><th>Contenido</th><th>Cantidad</th><th>Notas</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
            <tr>
                <td><?= e($i['name']) ?></td>
                <td class="mono"><?= e($i['quantity']) ?></td>
                <td class="muted"><?= e($i['notes'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$items): ?><div class="empty">Caja vacía.</div><?php endif; ?>
</section>
