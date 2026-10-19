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
            <article class="panel" style="margin:0">
                <a style="text-decoration:none;color:inherit" href="<?= e(url('/properties/' . $pid . '/boxes/' . $b['public_id'])) ?>">
                    <div class="mono muted"><?= e($b['code']) ?></div>
                    <h3 style="margin:.2rem 0"><?= e($b['name']) ?></h3>
                    <p class="muted" style="margin:0"><?= e($b['space_name'] ?? 'Sin espacio') ?> · <?= (int) $b['item_count'] ?> ítems</p>
                </a>
                <?php if (!empty($canEdit)): ?>
                    <form method="post" action="<?= e(url('/properties/' . $pid . '/boxes/' . $b['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Eliminar esta caja?');" style="margin-top:0.7rem">
                        <?= csrf_field() ?>
                        <button class="btn btn--ghost btn--sm" type="submit">Eliminar</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$boxes): ?><div class="empty">Sin cajas todavía.</div><?php endif; ?>
</section>
