<section>
    <h2>Iniciar sesión</h2>
    <p class="muted">Administrá hoy. Disfrutá mañana.</p>
    <form method="post" action="<?= e(url('/login')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Email <input type="email" name="email" value="<?= e((string) old('email')) ?>" required autofocus></label>
        <label>Contraseña <input type="password" name="password" required></label>
        <button class="btn" type="submit">Entrar</button>
    </form>
    <p class="muted" style="margin-top:1rem">
        <a href="<?= e(url('/register')) ?>">Crear cuenta</a> ·
        <a href="<?= e(url('/forgot-password')) ?>">Olvidé mi contraseña</a>
    </p>
</section>
