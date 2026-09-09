<section>
    <h2>Iniciar sesión</h2>
    <form method="post" action="<?= e(url('/login')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Email <input type="email" name="email" value="<?= e((string) old('email')) ?>" required></label>
        <label>Contraseña <input type="password" name="password" required></label>
        <button class="btn" type="submit">Entrar</button>
    </form>
    <p class="muted"><a href="<?= e(url('/register')) ?>">Crear cuenta</a> · <a href="<?= e(url('/forgot-password')) ?>">Olvidé mi contraseña</a></p>
</section>
