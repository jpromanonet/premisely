<?php /** @var array<string,mixed>|null $user */ ?>
<div class="page-header">
    <div>
        <h1>Configuración</h1>
        <p>Nombre, idioma, zona horaria y moneda preferida.</p>
    </div>
</div>
<section class="panel">
    <form method="post" action="<?= e(url($form_action ?? '/settings')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e((string) ($user['name'] ?? '')) ?>" required></label>
            <label>Email <input value="<?= e((string) ($user['email'] ?? '')) ?>" disabled></label>
            <label>Idioma
                <select name="locale">
                    <option value="es" <?= ($user['locale'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                    <option value="en" <?= ($user['locale'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                </select>
            </label>
            <label>Zona horaria <input name="timezone" value="<?= e((string) ($user['timezone'] ?? 'America/Argentina/Buenos_Aires')) ?>" required></label>
            <label>Moneda <input name="preferred_currency" maxlength="3" value="<?= e((string) ($user['preferred_currency'] ?? 'ARS')) ?>" required></label>
            <label><span>Notificaciones por email</span>
                <input type="checkbox" name="notify_email" value="1" <?= !empty($user['notify_email']) ? 'checked' : '' ?>>
            </label>
        </div>
        <button class="btn" type="submit">Guardar</button>
    </form>
</section>
