<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Espacios</h1>
        <p>Ambientes y ubicaciones dentro de la propiedad.</p>
    </div>
    <?php if (!empty($canEdit)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/spaces/create')) ?>">+ Nuevo espacio</a><?php endif; ?>
</div>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Tipo</th><th>Padre</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($spaces as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['type']) ?></td>
                    <td><?= e((string) ($s['parent_name'] ?? '—')) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <a class="btn btn--sm btn--ghost" href="<?= e(url('/properties/' . $pid . '/spaces/' . $s['id'] . '/edit')) ?>">Editar</a>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/spaces/' . $s['id'] . '/archive')) ?>" onsubmit="return confirm('¿Archivar?');" style="display:inline">
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
