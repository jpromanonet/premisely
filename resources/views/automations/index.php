<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $rules */
/** @var array<string,string> $labels */
/** @var bool $canManage */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Automatizaciones</h1><p>Reglas simples para stock, garantías y servicios.</p></div>
    <?php if (!empty($canManage)): ?>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/automations/run')) ?>">
        <?= csrf_field() ?>
        <button class="btn" type="submit">Ejecutar ahora</button>
    </form>
    <?php endif; ?>
</div>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Regla</th><th>Estado</th><th>Última ejecución</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rules as $r): ?>
                <tr>
                    <td><?= e($labels[$r['type']] ?? $r['type']) ?></td>
                    <td><?= (int) $r['enabled'] ? 'Activa' : 'Apagada' ?></td>
                    <td class="mono"><?= e($r['last_run_at'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($canManage)): ?>
                        <form method="post" action="<?= e(url('/properties/' . $pid . '/automations/' . $r['public_id'] . '/toggle')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn--ghost" type="submit"><?= (int) $r['enabled'] ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
