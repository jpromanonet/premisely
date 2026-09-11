<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $rules */
/** @var array<string,string> $labels */
/** @var array<string,string> $available */
/** @var bool $canManage */
$pid = $property['public_id'];
$available = $available ?? [];
?>
<div class="page-header">
    <div>
        <h1>Automatizaciones</h1>
        <p>Reglas simples para stock, garantías y servicios.</p>
    </div>
    <div class="actions">
        <?php if (!empty($canManage) && $available !== []): ?>
            <a class="btn" href="<?= e(url('/properties/' . $pid . '/automations/create')) ?>">+ Nueva</a>
        <?php endif; ?>
        <?php if (!empty($canManage)): ?>
            <form method="post" action="<?= e(url('/properties/' . $pid . '/automations/run')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn--ghost" type="submit">Ejecutar ahora</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Regla</th><th>Estado</th><th>Última ejecución</th><th></th></tr></thead>
            <tbody>
            <?php if ($rules === []): ?>
                <tr><td colspan="4" class="muted">No hay automatizaciones. <?php if (!empty($canManage)): ?><a href="<?= e(url('/properties/' . $pid . '/automations/create')) ?>">Creá la primera</a>.<?php endif; ?></td></tr>
            <?php endif; ?>
            <?php foreach ($rules as $r): ?>
                <tr>
                    <td><?= e($labels[$r['type']] ?? $r['type']) ?></td>
                    <td><?= (int) $r['enabled'] ? 'Activa' : 'Apagada' ?></td>
                    <td class="mono"><?= e($r['last_run_at'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($canManage)): ?>
                        <div class="actions">
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/automations/' . $r['public_id'] . '/toggle')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost" type="submit"><?= (int) $r['enabled'] ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/automations/' . $r['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar esta automatización?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger" type="submit">Eliminar</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
