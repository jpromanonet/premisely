<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $spaces */
/** @var bool $canEdit */
$pid = $property['public_id'];
?>
<div class="page-header"><div><h1>Espacios</h1><p>Ambientes y ubicaciones dentro de la propiedad.</p></div></div>

<?php if ($canEdit): ?>
<section class="panel">
    <h2>Nuevo espacio</h2>
    <form method="post" action="<?= e(url('/properties/' . $pid . '/spaces')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" required></label>
            <label>Tipo
                <select name="type">
                    <?php foreach (['ambiente','habitacion','baño','cocina','garage','jardin','deposito','otro'] as $t): ?>
                        <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Padre
                <select name="parent_id">
                    <option value="">— Ninguno —</option>
                    <?php foreach ($spaces as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Descripción <textarea name="description"></textarea></label>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Tipo</th><th>Padre</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($spaces as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['type']) ?></td>
                    <td><?= e((string) ($s['parent_name'] ?? '—')) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <details>
                                <summary>Editar</summary>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/spaces/' . $s['id'])) ?>" class="stack" style="margin-top:.5rem">
                                    <?= csrf_field() ?>
                                    <input name="name" value="<?= e($s['name']) ?>" required>
                                    <input name="type" value="<?= e($s['type']) ?>" required>
                                    <select name="parent_id">
                                        <option value="">— Ninguno —</option>
                                        <?php foreach ($spaces as $p): ?>
                                            <?php if ((int)$p['id'] === (int)$s['id']) continue; ?>
                                            <option value="<?= (int) $p['id'] ?>" <?= (int)($s['parent_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="description"><?= e((string) $s['description']) ?></textarea>
                                    <button class="btn btn--sm" type="submit">Guardar</button>
                                </form>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/spaces/' . $s['id'] . '/archive')) ?>" onsubmit="return confirm('¿Archivar?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--danger btn--sm" type="submit">Archivar</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
