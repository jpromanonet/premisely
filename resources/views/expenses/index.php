<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $expenses */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Gastos</h1></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/expenses/create')) ?>">+ Nuevo gasto</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Fecha</th><th>Título</th><th>Categoría</th><th>Monto</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $e): ?>
                <tr>
                    <td><?= e($e['spent_at']) ?></td>
                    <td><?= e($e['title']) ?></td>
                    <td><?= e((string)($e['category_name'] ?? '—')) ?></td>
                    <td><?= e((string)$e['amount']) ?> <?= e($e['currency']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses/' . $e['public_id'] . '/archive')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Archivar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
