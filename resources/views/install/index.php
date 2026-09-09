<div>
  <h2>Instalar Premisely</h2>
  <p class="muted">Asistente de instalación para el servidor local.</p>
  <div class="panel" style="margin:1rem 0">
    <h3>Requisitos</h3>
    <ul>
    <?php foreach ($checks as $label => $ok): ?>
      <li><?= e($label) ?>: <?= $ok ? 'OK' : 'FALTA' ?></li>
    <?php endforeach; ?>
    </ul>
  </div>
  <form method="post" action="<?= e(url('/install')) ?>">
    <?= csrf_field() ?>
    <h3>Base de datos</h3>
    <label>Host</label>
    <input name="db_host" value="127.0.0.1" required>
    <label>Puerto</label>
    <input name="db_port" value="3306">
    <label>Base</label>
    <input name="db_name" value="premisely" required>
    <label>Usuario</label>
    <input name="db_user" value="root" required>
    <label>Contraseña</label>
    <input name="db_pass" type="password" value="local_admin">
    <h3>Cuenta inicial</h3>
    <label>Nombre</label>
    <input name="name" required>
    <label>Email</label>
    <input name="email" type="email" required>
    <label>Contraseña</label>
    <input name="password" type="password" minlength="8" required>
    <label>Confirmar contraseña</label>
    <input name="password_confirmation" type="password" minlength="8" required>
    <div class="form-actions"><button class="btn" type="submit">Instalar</button></div>
  </form>
</div>
