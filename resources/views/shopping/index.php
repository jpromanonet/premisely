<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $list */
/** @var list<array<string,mixed>> $items */
/** @var bool $canEdit */
$pid = $property['public_id'];

$formatQty = static function (array $item): string {
    $qty = (float) ($item['quantity'] ?? 0);
    $text = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    if ($text === '' || $text === '0') {
        $text = '1';
    }
    $unit = trim((string) ($item['unit'] ?? ''));
    return $unit !== '' ? $text . ' ' . $unit : $text;
};

$pending = [];
$done = [];
foreach ($items as $item) {
    if (($item['status'] ?? '') === 'pending') {
        $pending[] = $item;
    } else {
        $done[] = $item;
    }
}

$lines = [];
foreach ($pending as $i => $item) {
    $lines[] = ($i + 1) . '. ' . $item['name'] . ' — ' . $formatQty($item);
}
$plain = implode("\n", $lines);
?>
<div class="page-header">
    <div>
        <h1>Lista de compras</h1>
        <p><?= count($pending) === 1 ? '1 pendiente' : count($pending) . ' pendientes' ?></p>
    </div>
    <div class="actions">
        <?php if ($pending !== []): ?>
            <button class="btn btn--ghost" type="button" data-copy-source="shopping-copy-text">Copiar lista</button>
            <script type="application/json" id="shopping-copy-text"><?= json_encode($plain, JSON_UNESCAPED_UNICODE) ?></script>
        <?php endif; ?>
        <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/shopping/create')) ?>">+ Nuevo ítem</a><?php endif; ?>
    </div>
</div>

<section class="panel">
    <?php if ($pending === [] && $done === []): ?>
        <p class="muted">La lista está vacía.</p>
    <?php endif; ?>

    <?php if ($pending !== []): ?>
        <ul class="shopping-list">
            <?php foreach ($pending as $item): ?>
                <li>
                    <div>
                        <div class="shopping-list__name"><?= e($item['name']) ?></div>
                        <div class="shopping-list__qty"><?= e($formatQty($item)) ?></div>
                    </div>
                    <?php if (!empty($canEdit)): ?>
                        <div class="actions" style="margin-top:0">
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'] . '/complete')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--sm" type="submit">Comprado</button>
                            </form>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'] . '/delete')) ?>" onsubmit="return confirm('¿Sacar este ítem?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Eliminar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($done !== []): ?>
        <h3 style="margin:1.25rem 0 .25rem">Ya comprados</h3>
        <ul class="shopping-list">
            <?php foreach ($done as $item): ?>
                <li class="is-done">
                    <div>
                        <div class="shopping-list__name"><?= e($item['name']) ?></div>
                        <div class="shopping-list__qty"><?= e($formatQty($item)) ?></div>
                    </div>
                    <?php if (!empty($canEdit)): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/shopping/' . $item['id'] . '/delete')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn--ghost btn--sm" type="submit">Quitar</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
