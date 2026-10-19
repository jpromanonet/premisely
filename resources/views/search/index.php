<?php
/** @var array<string,mixed> $property */
/** @var string $q */
/** @var array<string,list<array<string,mixed>>> $results */
$pid = $property['public_id'];
$groups = [
    'stock' => 'Stock',
    'shopping' => 'Compras',
    'inventory' => 'Inventario',
    'spaces' => 'Espacios',
    'tasks' => 'Tareas',
    'documents' => 'Documentos',
    'expenses' => 'Gastos',
];
$any = false;
foreach ($groups as $key => $label) {
    if (($results[$key] ?? []) !== []) {
        $any = true;
        break;
    }
}
?>
<div class="page-header"><div><h1>Buscar</h1><p>Stock, compras, inventario, tareas y el resto de la casa.</p></div></div>
<section class="panel">
    <form method="get" action="<?= e(form_target()) ?>" class="actions">
        <input type="hidden" name="r" value="/properties/<?= e($pid) ?>/search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar…" required style="min-width:240px">
        <button class="btn" type="submit">Buscar</button>
    </form>
</section>
<?php if ($q !== '' && !$any): ?>
    <p class="empty-note">Nada con “<?= e($q) ?>”.</p>
<?php endif; ?>
<?php if ($q !== ''): ?>
    <?php foreach ($groups as $key => $label): ?>
        <?php if (($results[$key] ?? []) === []) { continue; } ?>
        <section class="panel">
            <h2><?= e($label) ?></h2>
            <ul>
                <?php foreach ($results[$key] as $row): ?>
                    <li>
                        <?php if ($key === 'inventory'): ?>
                            <a href="<?= e(url('/properties/' . $pid . '/inventory/' . $row['public_id'])) ?>"><?= e($row['name']) ?></a>
                        <?php elseif ($key === 'stock'): ?>
                            <a href="<?= e(url('/properties/' . $pid . '/stock')) ?>"><?= e($row['name']) ?></a>
                            <span class="muted"><?= e(format_qty($row['quantity'])) ?> <?= e(unit_label($row['quantity'], (string) ($row['unit'] ?? ''))) ?></span>
                        <?php elseif ($key === 'shopping'): ?>
                            <a href="<?= e(url('/properties/' . $pid . '/shopping')) ?>"><?= e($row['name']) ?></a>
                            <?php if (trim((string) ($row['brand'] ?? '')) !== ''): ?><span class="muted"><?= e((string) $row['brand']) ?></span><?php endif; ?>
                        <?php elseif ($key === 'spaces'): ?>
                            <a href="<?= e(url('/properties/' . $pid . '/spaces')) ?>"><?= e($row['name']) ?></a>
                        <?php elseif ($key === 'tasks'): ?>
                            <a href="<?= e(url('/properties/' . $pid . '/calendar')) ?>"><?= e($row['title']) ?></a>
                        <?php elseif ($key === 'documents'): ?>
                            <a href="<?= e(url('/properties/' . $pid . '/documents')) ?>"><?= e($row['title']) ?></a>
                        <?php elseif ($key === 'expenses'): ?>
                            <a href="<?= e(url('/properties/' . $pid . '/expenses')) ?>"><?= e($row['title']) ?></a>
                            <span class="muted"><?= e((string) ($row['currency'] ?? 'ARS')) ?> <?= e(format_money($row['amount'])) ?></span>
                        <?php else: ?>
                            <?= e((string) ($row['name'] ?? $row['title'] ?? '')) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
