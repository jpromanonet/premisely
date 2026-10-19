<?php
/** @var array<string,mixed> $property */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Agregar miembro</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/members')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/members')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="display_name" required></label>
            <label>Email <input type="email" name="email" placeholder="Para poder entrar"></label>
            <label>Contraseña <input type="text" name="password" minlength="8" autocomplete="off" placeholder="Mínimo 8, se la pasás vos"></label>
            <label>Rol
                <select name="role">
                    <?php foreach (['member' => 'Integrante', 'admin' => 'Admin', 'collaborator' => 'Colaborador', 'viewer' => 'Solo ver'] as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tipo <input name="member_type" value="residente"></label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Agregar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/members')) ?>">Cancelar</a>
        </div>
    </form>
</section>
