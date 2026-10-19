<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $categories */
/** @var string $rubro */
/** @var bool $canEdit */
$pid = $property['public_id'];
$categories = $categories ?? [];
$rubro = (string) ($rubro ?? '');
$palette = rubro_palette();
$labels = ['sin' => 'Sin rubro'];
$groupOrder = [];
foreach ($categories as $category) {
    $slug = (string) $category['slug'];
    $labels[$slug] = (string) $category['name'];
    $groupOrder[] = $slug;
}
$groupOrder[] = 'sin';
$groups = [];
$zeroCount = 0;
foreach ($items as $item) {
    $slug = (string) ($item['category_slug'] ?? '');
    if ($slug === '' || !isset($labels[$slug])) {
        $slug = 'sin';
    }
    $groups[$slug][] = $item;
    if ((float) $item['quantity'] <= 0) {
        $zeroCount++;
    }
}
?>
<div class="page-header">
    <div>
        <h1>Stock</h1>
        <p><?= count($items) === 0 ? 'Nada cargado.' : 'Lo que hay en casa. Si llega a 0, pasa a compras.' ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="<?= e(url('/properties/' . $pid . '/shopping')) ?>">Compras</a>
        <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/stock/create')) ?>">+ Nuevo</a><?php endif; ?>
    </div>
</div>

<?php if ($items !== []): ?>
<div class="metric-grid">
    <div class="metric">
        <span>En casa</span>
        <strong><?= count($items) ?></strong>
        <p>ítems cargados</p>
    </div>
    <div class="metric<?= $zeroCount > 0 ? ' is-alert' : '' ?>">
        <span>En cero</span>
        <strong><?= $zeroCount ?></strong>
        <p>están en compras</p>
    </div>
</div>
<?php endif; ?>

<?php if ($items !== []): ?>
<nav class="rubro-filters">
    <select class="rubro-select" aria-label="Rubro" onchange="if (this.value) location.href = this.value">
        <option value="<?= e(url('/properties/' . $pid . '/stock')) ?>" <?= $rubro === '' ? 'selected' : '' ?>>Todos (<?= count($items) ?>)</option>
        <?php foreach ($categories as $category): ?>
            <?php $slug = (string) $category['slug']; ?>
            <option value="<?= e(url('/properties/' . $pid . '/stock', ['rubro' => $slug])) ?>" <?= $rubro === $slug ? 'selected' : '' ?>><?= e((string) $category['name']) ?> (<?= count($groups[$slug] ?? []) ?>)</option>
        <?php endforeach; ?>
        <?php if (($groups['sin'] ?? []) !== [] || $rubro === 'sin'): ?>
            <option value="<?= e(url('/properties/' . $pid . '/stock', ['rubro' => 'sin'])) ?>" <?= $rubro === 'sin' ? 'selected' : '' ?>>Sin rubro (<?= count($groups['sin'] ?? []) ?>)</option>
        <?php endif; ?>
    </select>
    <a class="<?= $rubro === '' ? 'is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/stock')) ?>">Todos <span class="rubro-n">(<?= count($items) ?>)</span></a>
    <?php foreach ($categories as $category): ?>
        <?php
        $slug = (string) $category['slug'];
        $tone = $palette[$slug] ?? $palette['sin'];
        ?>
        <a class="<?= $rubro === $slug ? 'is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/stock', ['rubro' => $slug])) ?>"><i style="--dot:#<?= e($tone['bg']) ?>"></i><?= e((string) $category['name']) ?> <span class="rubro-n">(<?= count($groups[$slug] ?? []) ?>)</span></a>
    <?php endforeach; ?>
    <?php if (($groups['sin'] ?? []) !== [] || $rubro === 'sin'): ?>
        <a class="<?= $rubro === 'sin' ? 'is-on' : '' ?>" href="<?= e(url('/properties/' . $pid . '/stock', ['rubro' => 'sin'])) ?>"><i style="--dot:#<?= e($palette['sin']['bg']) ?>"></i>Sin rubro <span class="rubro-n">(<?= count($groups['sin'] ?? []) ?>)</span></a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php if ($items === []): ?>
    <p class="empty-note">Acá va lo que hay en casa: sal, papel, lo que se gasta.</p>
<?php endif; ?>

<?php $shown = 0; ?>
<?php foreach ($groupOrder as $slug): ?>
    <?php if ($rubro !== '' && $rubro !== $slug) { continue; } ?>
    <?php if (($groups[$slug] ?? []) === []) { continue; } ?>
    <?php
    $shown += count($groups[$slug]);
    $tone = $palette[$slug] ?? $palette['sin'];
    ?>
    <section class="stock-group" style="--rubro:#<?= e($tone['bg']) ?>;--rubro-ink:#<?= e($tone['ink']) ?>">
        <h2 class="rubro-cut"><?= e($labels[$slug] ?? 'Sin rubro') ?> <span class="rubro-n">(<?= count($groups[$slug]) ?>)</span></h2>
        <?php foreach ($groups[$slug] as $item): ?>
            <?php
            $qty = (float) $item['quantity'];
            ?>
            <article class="stock-item" id="item-<?= e((string) $item['public_id']) ?>">
                <div class="stock-item__main">
                    <strong><?= e($item['name']) ?></strong>
                    <?php if (trim((string) ($item['brand'] ?? '')) !== ''): ?>
                        <p class="muted"><?= e((string) $item['brand']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="stock-step">
                    <?php if ($canEdit): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/stock/' . $item['public_id'] . '/adjust')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="consume">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" aria-label="Restar uno" <?= $qty <= 0 ? 'disabled' : '' ?>>−</button>
                        </form>
                    <?php endif; ?>
                    <div class="stock-step__n">
                        <b><?= e(format_qty($qty)) ?></b>
                        <span><?= e(unit_label($qty, (string) ($item['unit'] ?? ''))) ?></span>
                    </div>
                    <?php if ($canEdit): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/stock/' . $item['public_id'] . '/adjust')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="purchase">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" aria-label="Sumar uno">+</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if ($canEdit): ?>
                    <details class="stock-item__edit">
                        <summary>Editar</summary>
                        <div class="stock-item__panel">
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/stock/' . $item['public_id'])) ?>" class="stock-edit">
                                <?= csrf_field() ?>
                                <?php if ($item['target_quantity'] !== null && $item['target_quantity'] !== ''): ?>
                                    <input type="hidden" name="target_quantity" value="<?= e(format_qty_input($item['target_quantity'])) ?>">
                                <?php endif; ?>
                                <label>Nombre <input name="name" value="<?= e($item['name']) ?>" required></label>
                                <label>Marca <input name="brand" value="<?= e((string) ($item['brand'] ?? '')) ?>" placeholder="Opcional"></label>
                                <label>Rubro
                                    <select name="category_id">
                                        <option value="">Sin rubro</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= (int) $category['id'] ?>" <?= (string) ($item['category_slug'] ?? '') === (string) $category['slug'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Unidad <input name="unit" value="<?= e((string) $item['unit']) ?>"></label>
                                <button class="btn btn--sm" type="submit">Guardar</button>
                            </form>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/stock/' . $item['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Sacar este ítem del stock?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Sacar de la lista</button>
                            </form>
                        </div>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
<?php endforeach; ?>
<?php if ($items !== [] && $shown === 0): ?>
    <p class="empty-note">Nada en este rubro.</p>
<?php endif; ?>
