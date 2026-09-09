<?php
/** @var list<array<string,mixed>> $checks */
/** @var bool $ok */
?>
<section>
    <h2>Requisitos del sistema</h2>
    <ul>
        <?php foreach ($checks as $label => $pass): ?>
            <li><?= $pass ? '✓' : '✗' ?> <?= e($label) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php if ($ok): ?>
        <a class="btn" href="<?= e(url('/install/database')) ?>">Continuar</a>
    <?php else: ?>
        <p class="muted">Corregí los requisitos faltantes y recargá esta página.</p>
    <?php endif; ?>
</section>
