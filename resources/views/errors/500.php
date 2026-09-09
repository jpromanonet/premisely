<?php /** @var string|null $message */ ?>
<section class="panel">
    <h1>Error interno</h1>
    <p class="muted"><?= e($message ?? 'Ocurrió un problema inesperado.') ?></p>
    <a class="btn" href="<?= e(url('/dashboard')) ?>">Ir al panel</a>
</section>
