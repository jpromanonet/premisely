<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $members */
/** @var bool $canManage */
$pid = $property['public_id'];
$roleLabels = [
    'owner' => 'Propietario',
    'admin' => 'Admin',
    'member' => 'Miembro',
    'collaborator' => 'Colaborador',
    'viewer' => 'Visor',
];
$statusLabels = [
    'active' => 'Activo',
    'invited' => 'Invitado',
    'left' => 'Salió',
    'disabled' => 'Deshabilitado',
];
?>
<div class="page-header">
    <div><h1>Integrantes</h1><p>Personas con acceso a esta propiedad.</p></div>
    <?php if (!empty($canManage)): ?><a class="btn" href="<?= e(url('/properties/' . $pid . '/members/create')) ?>">+ Nuevo miembro</a><?php endif; ?>
</div>

<section class="panel">
    <?php foreach ($members as $m): ?>
        <?php if (!empty($canManage) && ($m['role'] ?? '') !== 'owner'): ?>
            <form id="member-edit-<?= (int) $m['id'] ?>" method="post" action="<?= e(url('/properties/' . $pid . '/members/' . $m['id'])) ?>" class="sr-only" aria-hidden="true">
                <?= csrf_field() ?>
            </form>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="table-wrap">
        <table class="members-table">
            <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($members as $m): ?>
                <?php
                $editable = !empty($canManage) && ($m['role'] ?? '') !== 'owner';
                $formId = 'member-edit-' . (int) $m['id'];
                ?>
                <tr>
                    <td>
                        <div class="member-cell">
                            <?php if (!empty($m['avatar_path']) && !empty($m['user_public_id'])): ?>
                                <span class="avatar avatar--img"><img src="<?= e(url('/avatars/' . $m['user_public_id'])) ?>" alt=""></span>
                            <?php elseif (!empty($m['display_name'])): ?>
                                <?php
                                $parts = preg_split('/\s+/', trim((string) $m['display_name'])) ?: [];
                                $ini = strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
                                ?>
                                <span class="avatar"><?= e($ini) ?></span>
                            <?php endif; ?>
                            <span><?= e((string) $m['display_name']) ?></span>
                        </div>
                    </td>
                    <td><?= e((string) ($m['email'] ?? '')) ?></td>
                    <td>
                        <?php if ($editable): ?>
                            <select class="select-compact" form="<?= e($formId) ?>" name="role">
                                <?php foreach (['admin','member','collaborator','viewer'] as $r): ?>
                                    <option value="<?= $r ?>" <?= $m['role'] === $r ? 'selected' : '' ?>><?= e($roleLabels[$r] ?? $r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?= e($roleLabels[$m['role']] ?? (string) $m['role']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($editable): ?>
                            <select class="select-compact" form="<?= e($formId) ?>" name="status">
                                <?php foreach (['active','invited','left','disabled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $m['status'] === $s ? 'selected' : '' ?>><?= e($statusLabels[$s] ?? $s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?= e($statusLabels[$m['status']] ?? (string) $m['status']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="members-table__actions">
                        <?php if ($editable): ?>
                            <button class="btn btn--sm" type="submit" form="<?= e($formId) ?>">Guardar</button>
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
