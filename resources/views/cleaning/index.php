<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $routines */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Limpieza</h1>
        <p>Rutinas con categoría cleaning.</p>
    </div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/cleaning/create')) ?>">+ Nueva rutina</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Espacio</th><th>Próxima</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($routines as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e((string)($r['space_name'] ?? '—')) ?></td>
                    <td><?= e((string)$r['next_due_at']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/cleaning/' . $r['public_id'] . '/execute')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect" value="<?= e('/properties/' . $pid . '/cleaning') ?>">
                                <button class="btn btn--sm" type="submit">Hecho</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
