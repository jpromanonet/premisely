<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $members */
/** @var bool $canManage */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Miembros</h1><p>Personas con acceso a esta propiedad.</p></div>
    <?php if (!empty($canManage)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/members/create')) ?>">+ Nuevo miembro</a><?php endif; ?>
</div>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td><?= e($m['display_name']) ?></td>
                    <td><?= e((string) $m['email']) ?></td>
                    <td><?= e($m['role']) ?></td>
                    <td><?= e($m['status']) ?></td>
                    <td>
                        <?php if ($canManage && $m['role'] !== 'owner'): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/members/' . $m['id'])) ?>" class="actions">
                                <?= csrf_field() ?>
                                <select name="role">
                                    <?php foreach (['admin','member','collaborator','viewer'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $m['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="status">
                                    <?php foreach (['active','invited','left','disabled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $m['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn--sm" type="submit">Actualizar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/members/leave')) ?>" onsubmit="return confirm('¿Abandonar la propiedad?');" style="margin-top:1rem">
        <?= csrf_field() ?>
        <button class="btn btn--ghost" type="submit">Abandonar propiedad</button>
    </form>
</section>
