<section>
    <h2>Crear cuenta</h2>
    <form method="post" action="<?= e(url('/register')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Nombre <input name="name" value="<?= e((string) old('name')) ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= e((string) old('email')) ?>" required></label>
        <label>Contraseña <input type="password" name="password" minlength="8" required></label>
        <label>Confirmar <input type="password" name="password_confirmation" minlength="8" required></label>
        <button class="btn" type="submit">Registrarme</button>
    </form>
    <p class="muted"><a href="<?= e(url('/login')) ?>">Ya tengo cuenta</a></p>
</section>
