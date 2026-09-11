<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $routines */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Rutinas</h1></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/routines/create')) ?>">+ Nueva rutina</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Categoría</th><th>Próxima</th><th>Última</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($routines as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['category']) ?></td>
                    <td><?= e((string)$r['next_due_at']) ?></td>
                    <td><?= e((string)$r['last_executed_at']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $r['public_id'] . '/execute')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--sm" type="submit">Ejecutar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
