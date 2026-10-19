<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $list */
/** @var list<array<string,mixed>> $items */
/** @var array<string,string> $rubros */
/** @var string $rubro */
/** @var bool $canEdit */
$pid = $property['public_id'];
$rubros = $rubros ?? shopping_rubros();
$rubro = (string) ($rubro ?? '');
$palette = rubro_palette();

$groups = [];
foreach ($items as $item) {
    $key = (string) ($item['category'] ?? '');
    if (!isset($rubros[$key])) {
        $key = 'sin';
    }
    $groups[$key][] = $item;
}
$groupOrder = array_keys($rubros);
$groupOrder[] = 'sin';
$shown = 0;
foreach ($groupOrder as $key) {
    if ($rubro !== '' && $rubro !== $key) {
        continue;
    }
    $shown += count($groups[$key] ?? []);
}
?>
<div class="page-header">
    <div>
        <h1>Compras</h1>
        <p><?= $shown === 0 ? 'Nada para comprar.' : $shown . ' para comprar' ?></p>
    </div>
    <div class="actions">
        <a class="btn btn--soft" href="<?= e(url('/properties/' . $pid . '/shopping/export')) ?>">Exportar</a>
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/stock')) ?>">Stock</a>
        <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/shopping/create')) ?>">+ Nuevo</a><?php endif; ?>
    </div>
</div>

<nav class="rubro-filters">
    <select class="rubro-select" aria-label="Rubro" onchange="if (this.value) location.href = this.value">
        <option value="<?= e(url('/properties/' . $pid . '/shopping')) ?>" <?= $rubro === '' ? 'selected' : '' ?>>Todos</option>
        <?php foreach ($rubros as $key => $label): ?>
            <option value="<?= e(url('/properties/' . $pid . '/shopping', ['rubro' => $key])) ?>" <?= $rubro === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
        <?php if (($groups['sin'] ?? []) !== [] || $rubro === 'sin'): ?>
            <option value="<?= e(url('/properties/' . $pid . '/shopping', ['rubro' => 'sin'])) ?>" <?= $rubro === 'sin' ? 'selected' : '' ?>>Sin rubro</option>
        <?php endif; ?>
    </select>
    <a class="<?= $rubro === '' ? 'is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">Todos</a>
    <?php foreach ($rubros as $key => $label): ?>
        <?php $tone = $palette[$key] ?? $palette['sin']; ?>
        <a class="<?= $rubro === $key ? 'is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/shopping', ['rubro' => $key])) ?>"><i style="--dot:#<?= e($tone['bg']) ?>"></i><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if (($groups['sin'] ?? []) !== [] || $rubro === 'sin'): ?>
        <a class="<?= $rubro === 'sin' ? 'is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/shopping', ['rubro' => 'sin'])) ?>"><i style="--dot:#<?= e($palette['sin']['bg']) ?>"></i>Sin rubro</a>
    <?php endif; ?>
</nav>

<?php if ($items === []): ?>
    <p class="empty-note">Acá va lo que falta comprar.</p>
<?php elseif ($shown === 0): ?>
    <p class="empty-note">Nada en este rubro.</p>
<?php endif; ?>

<?php foreach ($groupOrder as $key): ?>
    <?php if ($rubro !== '' && $rubro !== $key) { continue; } ?>
    <?php if (($groups[$key] ?? []) === []) { continue; } ?>
    <?php $tone = $palette[$key] ?? $palette['sin']; ?>
    <section class="stock-group" style="--rubro:#<?= e($tone['bg']) ?>;--rubro-ink:#<?= e($tone['ink']) ?>">
        <h2 class="rubro-cut"><?= $key === 'sin' ? 'Sin rubro' : e($rubros[$key]) ?></h2>
        <?php foreach ($groups[$key] as $item): ?>
            <?php $qty = (float) ($item['quantity'] ?? 0); ?>
            <article class="stock-item" id="item-<?= (int) $item['id'] ?>">
                <div class="stock-item__main">
                    <strong><?= e($item['name']) ?></strong>
                    <?php if (trim((string) ($item['brand'] ?? '')) !== ''): ?>
                        <p class="muted"><?= e((string) $item['brand']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="stock-item__qty">
                    <b><?= e(format_qty($qty)) ?></b>
                    <span><?= e(unit_label($qty, (string) ($item['unit'] ?? ''))) ?> a comprar</span>
                </div>
                <?php if (!empty($canEdit)): ?>
                    <div class="stock-actions">
                        <form class="buy-form" method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'] . '/complete')) ?>">
                            <?= csrf_field() ?>
                            <label class="buy-qty">Cuántas compré
                                <input type="number" name="bought_quantity" min="1" step="1" required value="<?= e(format_qty_input($qty > 0 ? $qty : 1)) ?>" aria-label="Cuántas compré">
                            </label>
                            <button class="btn btn--sm" type="submit">Ya lo compré</button>
                        </form>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'] . '/delete')) ?>" onsubmit="return confirm('¿Sacar este ítem?');">
                            <?= csrf_field() ?>
                            <button class="btn btn--ghost btn--sm" type="submit">Sacar</button>
                        </form>
                    </div>
                    <details class="stock-item__edit">
                        <summary>Editar</summary>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'])) ?>" class="stock-edit">
                            <?= csrf_field() ?>
                            <label>Producto <input name="name" value="<?= e((string) $item['name']) ?>" required></label>
                            <label>Marca <input name="brand" value="<?= e((string) ($item['brand'] ?? '')) ?>" placeholder="Opcional"></label>
                            <label>Rubro
                                <select name="category">
                                    <option value="">Sin rubro</option>
                                    <?php foreach ($rubros as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= (string) ($item['category'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Cantidad a comprar <input type="number" step="1" min="1" name="quantity" value="<?= e(format_qty_input($qty > 0 ? $qty : 1)) ?>" required></label>
                            <label>Unidad <input name="unit" value="<?= e((string) ($item['unit'] ?? '')) ?>"></label>
                            <button class="btn btn--sm" type="submit">Guardar</button>
                        </form>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
<?php endforeach; ?>
