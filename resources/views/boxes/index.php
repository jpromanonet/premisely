<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $boxes */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Cajas y almacenamiento</h1><p>Contenedores físicos y su contenido.</p></div>
</div>
<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h2>Nueva caja</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/boxes')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Código <input name="code" placeholder="C12"></label>
            <label>Nombre <input name="name" required></label>
            <label>Espacio
                <select name="space_id"><option value="">—</option>
                    <?php foreach ($spaces as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>
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
