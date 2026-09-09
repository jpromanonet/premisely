<section>
    <h2>Usuario administrador</h2>
    <form method="post" action="<?= e(url('/install/admin')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Nombre <input name="name" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Contraseña <input type="password" name="password" minlength="8" required></label>
        <label>Confirmar contraseña <input type="password" name="password_confirmation" minlength="8" required></label>
        <button class="btn" type="submit">Crear administrador</button>
    </form>
</section>
