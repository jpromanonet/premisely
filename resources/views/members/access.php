<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $member */
$pid = $property['public_id'];
$hasUser = !empty($member['user_id']);
?>
<div class="page-header">
    <div>
        <h1><?= $hasUser ? 'Cambiar clave' : 'Crear usuario' ?></h1>
        <p><?= e((string) $member['display_name']) ?>. Después le pasás el email y la clave.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/members')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/members/' . $member['id'] . '/access')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Email
                <input type="email" name="email" value="<?= e((string) ($member['email'] ?? '')) ?>" <?= $hasUser ? 'readonly' : 'required' ?>>
            </label>
            <label>Contraseña
                <input type="text" name="password" required minlength="8" autocomplete="off" placeholder="Mínimo 8">
            </label>
        </div>
        <div class="actions">
            <button class="btn" type="submit"><?= $hasUser ? 'Guardar clave' : 'Crear usuario' ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/members')) ?>">Cancelar</a>
        </div>
    </form>
</section>
