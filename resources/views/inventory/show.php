<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $item */
/** @var list<array<string,mixed>> $events */
/** @var list<array<string,mixed>> $warranties */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
$warranties = $warranties ?? [];
$spaces = $spaces ?? [];
?>
<div class="page-header">
    <div>
        <h1><?= e($item['name']) ?></h1>
        <p><?= e((string) ($item['brand'] ?? '')) ?> <?= e((string) ($item['model'] ?? '')) ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/qr')) ?>">QR</a>
        <?php if (!empty($canEdit)): ?>
            <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/edit')) ?>">Editar</a>
        <?php endif; ?>
    </div>
</div>

<section class="panel">
    <p><strong>Estado:</strong> <span class="badge badge-ok"><?= e($item['status']) ?></span>
       · <strong>Condición:</strong> <?= e($item['condition']) ?></p>
    <p><strong>Valor estimado:</strong>
        <?= $item['estimated_value'] !== null ? e(number_format((float)$item['estimated_value'], 0, ',', '.')) : '—' ?>
    </p>
    <p><strong>Garantía hasta:</strong> <span class="mono"><?= e($item['warranty_until'] ?? '—') ?></span></p>
    <p><?= nl2br(e((string) ($item['description'] ?? ''))) ?></p>
</section>

<?php if (!empty($canEdit)): ?>
<section class="panel">
    <h3>Mover</h3>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/move')) ?>" class="actions">
        <?= csrf_field() ?>
        <select name="space_id">
            <option value="">Sin espacio</option>
            <?php foreach ($spaces as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= ((int)($item['space_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn--sm" type="submit">Mover</button>
    </form>
</section>

<section class="panel">
    <h3>Garantías</h3>
    <?php if ($warranties === []): ?><p class="muted">Sin garantías registradas.</p><?php endif; ?>
    <ul>
        <?php foreach ($warranties as $w): ?>
            <li>
                <strong><?= e($w['provider_name'] ?? $w['manufacturer'] ?? 'Garantía') ?></strong>
                · <span class="mono"><?= e($w['starts_on'] ?? '?') ?> → <?= e($w['ends_on'] ?? '?') ?></span>
                <?php if ($w['conditions_text']): ?><div class="muted"><?= e($w['conditions_text']) ?></div><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/warranties')) ?>" class="stack" style="margin-top:1rem">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Comercio / proveedor <input name="provider_name"></label>
            <label>Fabricante <input name="manufacturer"></label>
            <label>Inicio <input type="date" name="starts_on"></label>
            <label>Vence <input type="date" name="ends_on"></label>
        </div>
        <label>Condiciones <textarea name="conditions_text"></textarea></label>
        <button class="btn" type="submit">Agregar garantía</button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h3>Historial</h3>
    <?php if ($events === []): ?><p class="muted">Sin eventos.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($events as $ev): ?>
                <li><span class="mono muted"><?= e($ev['created_at']) ?></span> · <?= e($ev['event_type']) ?> <?= e((string)($ev['notes'] ?? '')) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
