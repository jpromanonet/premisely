<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $boxes */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Cajas y almacenamiento</h1><p>Contenedores físicos y su contenido.</p></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/boxes/create')) ?>">+ Nueva caja</a><?php endif; ?>
</div>
<section class="panel">
    <div class="card-grid">
        <?php foreach ($boxes as $b): ?>
            <a class="panel" style="margin:0;text-decoration:none;color:inherit" href="<?= e(url('/properties/' . $pid . '/boxes/' . $b['public_id'])) ?>">
                <div class="mono muted"><?= e($b['code']) ?></div>
                <h3 style="margin:.2rem 0"><?= e($b['name']) ?></h3>
                <p class="muted" style="margin:0"><?= e($b['space_name'] ?? 'Sin espacio') ?> · <?= (int)$b['item_count'] ?> ítems</p>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if (!$boxes): ?><div class="empty">Sin cajas todavía.</div><?php endif; ?>
</section>
