<?php
/** @var list<array<string,mixed>> $notifications */
?>
<div class="page-header">
    <div><h1>Notificaciones</h1><p>Alertas de stock, garantías, servicios y más.</p></div>
</div>
<section class="panel">
    <?php if ($notifications === []): ?>
        <p class="muted">Sin notificaciones.</p>
    <?php else: ?>
        <ul class="stack">
            <?php foreach ($notifications as $n): ?>
                <li style="<?= $n['read_at'] ? 'opacity:.6' : '' ?>">
                    <strong><?= e($n['title']) ?></strong>
                    <?php if (!empty($n['property_name'])): ?>
                        <span class="muted"> · <?= e($n['property_name']) ?></span>
                    <?php endif; ?>
                    <div class="muted"><?= e((string) ($n['body'] ?? '')) ?> · <?= e($n['created_at']) ?></div>
                    <div class="actions">
                        <?php if (!empty($n['link_path'])): ?>
                            <a href="<?= e(url($n['link_path'])) ?>">Abrir</a>
                        <?php endif; ?>
                        <?php if (!$n['read_at']): ?>
                        <form method="post" action="<?= e(url('/notifications/' . $n['public_id'] . '/read')) ?>" style="display:inline">
                            <?= csrf_field() ?>
                            <button class="btn btn--ghost" type="submit">Marcar leída</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
