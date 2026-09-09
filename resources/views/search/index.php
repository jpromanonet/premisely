<?php
/** @var array<string,mixed> $property */
/** @var string $q */
/** @var array<string,list<array<string,mixed>>> $results */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Buscar</h1><p>Inventario, stock, tareas y espacios.</p></div></div>
<section class="panel">
    <form method="get" action="<?= e(url('/properties/' . $pid . '/search')) ?>" class="actions">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar…" minlength="2" required style="min-width:240px">
        <button class="btn" type="submit">Buscar</button>
    </form>
</section>
<?php if ($q !== ''): ?>
    <?php foreach (['inventory' => 'Inventario', 'stock' => 'Stock', 'tasks' => 'Tareas', 'spaces' => 'Espacios'] as $key => $label): ?>
        <section class="panel">
            <h2><?= e($label) ?></h2>
            <?php if (($results[$key] ?? []) === []): ?>
                <p class="muted">Sin resultados.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($results[$key] as $row): ?>
                        <li>
                            <?php if ($key === 'inventory'): ?>
                                <a href="<?= e(url('/properties/' . $pid . '/inventory/' . $row['public_id'])) ?>"><?= e($row['name']) ?></a>
                            <?php elseif ($key === 'tasks'): ?>
                                <?= e($row['title']) ?> <span class="badge"><?= e($row['status']) ?></span>
                            <?php else: ?>
                                <?= e($row['name']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
