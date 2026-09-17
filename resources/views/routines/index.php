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
            <thead><tr><th>Título</th><th>Categoría</th><th>Estado</th><th>Próxima</th><th>Última</th><th data-nosort></th></tr></thead>
            <tbody>
            <?php if ($routines === []): ?>
                <tr><td colspan="6" class="muted">No hay rutinas todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($routines as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['category']) ?></td>
                    <td><?= (int) $r['is_active'] === 1 ? 'Activa' : 'Pausada' ?></td>
                    <td><?= e((string) ($r['next_due_at'] ?? '—')) ?></td>
                    <td><?= e((string) ($r['last_executed_at'] ?? '—')) ?></td>
                    <td>
                        <?php if (!empty($canEdit)): ?>
                            <div class="actions" style="margin-top:0">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('/properties/' . $pid . '/routines/' . $r['public_id'] . '/edit')) ?>">Editar</a>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $r['public_id'] . '/execute')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--sm" type="submit">Ejecutar</button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $r['public_id'] . '/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--ghost btn--sm" type="submit"><?= (int) $r['is_active'] === 1 ? 'Pausar' : 'Activar' ?></button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/routines/' . $r['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta rutina?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
