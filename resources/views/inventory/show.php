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

$money = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    return number_format((float) $value, 2, ',', '.');
};
$val = static function (mixed $value): string {
    $v = trim((string) ($value ?? ''));
    return $v !== '' ? $v : '—';
};

$subtitleParts = array_values(array_filter([
    trim((string) ($item['brand'] ?? '')),
    trim((string) ($item['model'] ?? '')),
    trim((string) ($item['space_name'] ?? '')),
], static fn (string $p): bool => $p !== ''));
?>
<div class="page-header">
    <div>
        <h1><?= e($item['name']) ?></h1>
        <?php if ($subtitleParts !== []): ?>
            <p><?= e(implode(' · ', $subtitleParts)) ?></p>
        <?php endif; ?>
    </div>
    <div class="actions">
        <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/inventory')) ?>">← Volver</a>
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/qr')) ?>">QR</a>
        <?php if (!empty($canEdit)): ?>
            <a class="btn" href="<?= e(url('/properties/' . $pid . '/inventory/' . $item['public_id'] . '/edit')) ?>">Editar</a>
        <?php endif; ?>
    </div>
</div>

<section class="panel">
    <h2>Ficha</h2>
    <div class="form-grid">
        <div><span class="muted">Estado</span><div><span class="badge badge-ok"><?= e($val($item['status'] ?? null)) ?></span></div></div>
        <div><span class="muted">Condición</span><div><?= e($val($item['condition'] ?? null)) ?></div></div>
        <div><span class="muted">Espacio</span><div><?= e($val($item['space_name'] ?? null)) ?></div></div>
        <div><span class="muted">Categoría</span><div><?= e($val($item['category_name'] ?? null)) ?></div></div>
        <div><span class="muted">Integrante</span><div><?= e($val($item['owner_name'] ?? null)) ?></div></div>
        <div><span class="muted">Propiedad de</span><div><?= e($val($item['ownership_type'] ?? null)) ?></div></div>
        <div><span class="muted">Marca</span><div><?= e($val($item['brand'] ?? null)) ?></div></div>
        <div><span class="muted">Modelo</span><div><?= e($val($item['model'] ?? null)) ?></div></div>
        <div><span class="muted">Nº serie</span><div class="mono"><?= e($val($item['serial_number'] ?? null)) ?></div></div>
        <div><span class="muted">Código interno</span><div class="mono"><?= e($val($item['internal_code'] ?? null)) ?></div></div>
        <div><span class="muted">Fecha de compra</span><div class="mono"><?= e($val($item['purchase_date'] ?? null)) ?></div></div>
        <div><span class="muted">Comercio</span><div><?= e($val($item['purchase_store'] ?? null)) ?></div></div>
        <div><span class="muted">Precio de compra</span><div><?= e($money($item['purchase_price'] ?? null)) ?><?php if (!empty($item['purchase_currency'])): ?> <?= e((string) $item['purchase_currency']) ?><?php endif; ?></div></div>
        <div><span class="muted">Valor estimado</span><div><?= e($money($item['estimated_value'] ?? null)) ?><?php if (!empty($item['estimated_value_currency'])): ?> <?= e((string) $item['estimated_value_currency']) ?><?php endif; ?></div></div>
        <div><span class="muted">Garantía hasta</span><div class="mono"><?= e($val($item['warranty_until'] ?? null)) ?></div></div>
    </div>
    <?php if (!empty($item['description'])): ?>
        <h3 style="margin-top:1.25rem">Descripción</h3>
        <p><?= nl2br(e((string) $item['description'])) ?></p>
    <?php endif; ?>
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
