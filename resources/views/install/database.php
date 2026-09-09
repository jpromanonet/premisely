<?php /** @var array<string,mixed> $config */ ?>
<section>
    <h2>Base de datos</h2>
    <p class="muted">Se creará la base si no existe, y se ejecutarán migraciones y seeds.</p>
    <form method="post" action="<?= e(url('/install/database')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Host <input name="host" value="<?= e((string) ($config['host'] ?? '127.0.0.1')) ?>" required></label>
            <label>Puerto <input name="port" value="<?= e((string) ($config['port'] ?? '3306')) ?>" required></label>
            <label>Base <input name="database" value="<?= e((string) ($config['database'] ?? 'premisely')) ?>" required></label>
            <label>Usuario <input name="username" value="<?= e((string) ($config['username'] ?? 'root')) ?>" required></label>
            <label>Contraseña <input type="password" name="password" value="local_admin"></label>
        </div>
        <button class="btn" type="submit">Crear y migrar</button>
    </form>
</section>
