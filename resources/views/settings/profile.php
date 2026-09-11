<?php
/** @var array<string,mixed>|null $user */
$hasAvatar = !empty($user['avatar_path']);
$avatarUrl = $hasAvatar ? url('/avatars/' . ($user['public_id'] ?? '')) : '';
$initials = '';
if (!empty($user['name'])) {
    $parts = preg_split('/\s+/', trim((string) $user['name'])) ?: [];
    $initials = strtoupper(mb_substr($parts[0] ?? 'P', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
}
?>
<div class="page-header">
    <div>
        <h1>Configuración</h1>
        <p>Foto, datos de cuenta, preferencias y contraseña.</p>
    </div>
</div>

<section class="panel">
    <h2>Perfil</h2>
    <form method="post" action="<?= e(url($form_action ?? '/settings/profile')) ?>" enctype="multipart/form-data" class="stack">
        <?= csrf_field() ?>

        <div class="profile-avatar-row">
            <?php if ($hasAvatar): ?>
                <span class="avatar avatar--lg avatar--img"><img src="<?= e($avatarUrl) ?>" alt=""></span>
            <?php else: ?>
                <span class="avatar avatar--lg"><?= e($initials !== '' ? $initials : 'P') ?></span>
            <?php endif; ?>
            <div class="stack" style="flex:1">
                <label>Foto de perfil
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                </label>
                <p class="muted" style="margin:0">JPG, PNG, GIF o WebP. Máx. <?= e((string) config('storage.max_mb', 10)) ?> MB.</p>
                <?php if ($hasAvatar): ?>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="remove_avatar" value="1">
                        Quitar foto actual
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e((string) ($user['name'] ?? '')) ?>" required></label>
            <label>Email <input type="email" name="email" value="<?= e((string) ($user['email'] ?? '')) ?>" required></label>
            <label>Idioma
                <select name="locale">
                    <option value="es" <?= ($user['locale'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                    <option value="en" <?= ($user['locale'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                </select>
            </label>
            <label>Zona horaria <input name="timezone" value="<?= e((string) ($user['timezone'] ?? 'America/Argentina/Buenos_Aires')) ?>" required></label>
            <label>Moneda <input name="preferred_currency" maxlength="3" value="<?= e((string) ($user['preferred_currency'] ?? 'ARS')) ?>" required></label>
            <label class="checkbox-inline"><span>Notificaciones por email</span>
                <input type="checkbox" name="notify_email" value="1" <?= !empty($user['notify_email']) ? 'checked' : '' ?>>
            </label>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Guardar perfil</button>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Cambiar contraseña</h2>
    <form method="post" action="<?= e(url($password_action ?? '/settings/password')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Contraseña actual <input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>Nueva contraseña <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            <label>Confirmar nueva <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
        </div>
        <p class="muted" style="margin:0">Mínimo 8 caracteres.</p>
        <div class="actions">
            <button class="btn" type="submit">Actualizar contraseña</button>
        </div>
    </form>
</section>
