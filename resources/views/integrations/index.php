<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $settings */
/** @var list<array<string,mixed>> $deliveries */
/** @var bool $canManage */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Integraciones</h1><p>Webhooks salientes y Home Assistant opcional.</p></div>
</div>
<?php if (!empty($canManage)): ?>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/integrations')) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Webhook URL <input name="webhook_url" value="<?= e((string) ($settings['webhook_url'] ?? '')) ?>" placeholder="https://..."></label>
        <label>Webhook secret <input name="webhook_secret" value="<?= e((string) ($settings['webhook_secret'] ?? '')) ?>"></label>
        <label><input type="checkbox" name="ha_enabled" value="1" <?= !empty($settings['ha_enabled']) ? 'checked' : '' ?>> Habilitar Home Assistant</label>
        <label>HA secret <input name="ha_secret" value="<?= e((string) ($settings['ha_secret'] ?? '')) ?>"></label>
        <p class="muted">Endpoint HA: <code><?= e(url('/api/v1/properties/' . $pid . '/integrations/ha')) ?></code> con header <code>X-Premisely-Secret</code>.</p>
        <button class="btn" type="submit">Guardar</button>
    </form>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Últimos webhooks</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Evento</th><th>Estado</th><th>Código</th><th>Cuándo</th></tr></thead>
            <tbody>
            <?php if ($deliveries === []): ?>
                <tr><td colspan="4" class="muted">Sin entregas todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td><?= e($d['event_type']) ?></td>
                    <td><?= e($d['status']) ?></td>
                    <td class="mono"><?= e((string) ($d['response_code'] ?? '—')) ?></td>
                    <td class="mono"><?= e($d['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
