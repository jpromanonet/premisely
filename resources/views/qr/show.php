<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $item */
/** @var string $target */
/** @var string $qrImage */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>QR · <?= e($item['name']) ?></h1>
        <p>Escaneá para abrir la ficha del objeto.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'])) ?>">← Volver</a>
</div>
<section class="panel" style="text-align:center">
    <img src="<?= e($qrImage) ?>" alt="QR de <?= e($item['name']) ?>" width="220" height="220" style="border-radius:16px;border:1px solid var(--line);background:#fff;padding:.75rem">
    <p class="mono muted" style="margin-top:1rem;word-break:break-all"><?= e($target) ?></p>
    <p class="muted">ID público: <span class="mono"><?= e($item['public_id']) ?></span></p>
</section>
