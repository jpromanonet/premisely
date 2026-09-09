<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $suggestions */
?>
<div class="page-header">
    <div><h1>Sugerencias</h1><p>Acciones recomendadas según el estado de la propiedad.</p></div>
</div>
<section class="panel">
    <?php if ($suggestions === []): ?>
        <p class="muted">Todo en orden por ahora.</p>
    <?php else: ?>
        <ul class="stack">
            <?php foreach ($suggestions as $s): ?>
                <li>
                    <strong><?= e($s['title']) ?></strong>
                    <span class="muted"> · <?= e($s['detail']) ?></span>
                    <a href="<?= e($s['href']) ?>">Ir</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
