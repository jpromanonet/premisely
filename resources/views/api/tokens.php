<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $tokens */
/** @var string|null $plainToken */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>API tokens</h1><p>Acceso Bearer para integraciones y apps.</p></div>
</div>
<?php if (!empty($plainToken)): ?>
<section class="panel">
    <h2>Nuevo token (copiá ahora)</h2>
    <p class="mono" style="word-break:break-all"><?= e($plainToken) ?></p>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Crear token</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/api-tokens')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Nombre <input name="name" value="Integración" required></label>
        <button class="btn" type="submit">Generar</button>
    </form>
</section>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Prefijo</th><th>Último uso</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($tokens as $t): ?>
                <tr>
                    <td><?= e($t['name']) ?></td>
                    <td class="mono"><?= e($t['token_prefix']) ?>…</td>
                    <td class="mono"><?= e($t['last_used_at'] ?? '—') ?></td>
                    <td><?= $t['revoked_at'] ? 'Revocado' : 'Activo' ?></td>
                    <td>
                        <?php if (!$t['revoked_at']): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/api-tokens/' . $t['public_id'] . '/revoke')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn--danger" type="submit">Revocar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="muted">Ejemplo: <code>Authorization: Bearer prm_…</code> contra <code>/api/v1/properties</code>.</p>
</section>
