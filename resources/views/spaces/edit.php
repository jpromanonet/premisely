<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $space */
/** @var list<array<string,mixed>> $spaces */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Editar espacio</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/spaces')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/spaces/' . $space['id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e($space['name']) ?>" required></label>
            <label>Tipo
                <select name="type">
                    <?php foreach (['ambiente','habitacion','baño','cocina','garage','jardin','deposito','otro'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($space['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Padre
                <select name="parent_id">
                    <option value="">— Ninguno —</option>
                    <?php foreach ($spaces as $p): ?>
                        <?php if ((int)$p['id'] === (int)$space['id']) continue; ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int)($space['parent_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"><?= e((string) $space['description']) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/spaces')) ?>">Cancelar</a>
        </div>
    </form>
</section>
