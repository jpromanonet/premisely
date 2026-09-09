<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $item */
/** @var list<array<string,mixed>> $events */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1><?= e($item['name']) ?></h1>
        <p><?= e((string) ($item['brand'] ?? '')) ?> <?= e((string) ($item['model'] ?? '')) ?></p>
    </div>
    <?php if ($canEdit): ?>
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/edit')) ?>">Editar</a>
    <?php endif; ?>
</div>
<section class="panel">
    <p><strong>Espacio:</strong> <?= e((string) ($item['space_name'] ?? '—')) ?></p>
    <p><strong>Categoría:</strong> <?= e((string) ($item['category_name'] ?? '—')) ?></p>
    <p><strong>Estado:</strong> <?= e($item['status']) ?> · <strong>Condición:</strong> <?= e($item['condition']) ?></p>
    <p><?= nl2br(e((string) ($item['description'] ?? ''))) ?></p>
</section>
<?php if ($canEdit): ?>
<section class="panel">
    <h3>Mover</h3>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/move')) ?>" class="actions">
        <?= csrf_field() ?>
        <input type="number" name="space_id" placeholder="ID espacio" value="<?= e((string) ($item['space_id'] ?? '')) ?>">
        <button class="btn btn--sm" type="submit">Mover</button>
    </form>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Archivar?');" style="margin-top:1rem">
        <?= csrf_field() ?>
        <button class="btn btn--danger btn--sm" type="submit">Archivar</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <h3>Eventos</h3>
    <?php if ($events === []): ?><p class="muted">Sin eventos.</p>
    <?php else: ?>
        <ul><?php foreach ($events as $ev): ?><li><?= e($ev['event_type']) ?> · <?= e((string)$ev['notes']) ?> <span class="muted"><?= e($ev['created_at']) ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
</section>
