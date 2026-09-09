<section>
    <h2>Recuperar contraseña</h2>
    <p class="muted">En V1 solo mostramos un mensaje de confirmación.</p>
    <form method="post" action="<?= e(url('/forgot-password')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Email <input type="email" name="email" required></label>
        <button class="btn" type="submit">Enviar</button>
    </form>
    <p class="muted"><a href="<?= e(url('/login')) ?>">Volver al login</a></p>
</section>
