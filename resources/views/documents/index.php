<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $documents */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Documentos</h1></div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/documents/create')) ?>">+ Nuevo documento</a><?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Categoría</th><th>Subido por</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($documents as $d): ?>
                <tr>
                    <td><?= e($d['title']) ?></td>
                    <td><?= e($d['category']) ?></td>
                    <td><?= e((string)($d['uploader_name'] ?? '—')) ?></td>
                    <td><?= e($d['created_at']) ?></td>
                    <td class="actions">
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('/files/documents/' . $d['public_id'])) ?>">Descargar</a>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/documents/' . $d['public_id'] . '/archive')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger btn--sm" type="submit">Archivar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
