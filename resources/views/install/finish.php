<section>
    <h2>Instalación lista</h2>
    <p>Se marcará la instalación como completa y podrás usar Premisely.</p>
    <form method="post" action="<?= e(url('/install/finish')) ?>">
        <?= csrf_field() ?>
        <button class="btn" type="submit">Finalizar</button>
    </form>
</section>
