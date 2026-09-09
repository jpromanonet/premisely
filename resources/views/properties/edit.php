<?php /** @var array<string,mixed> $property */ ?>
<div class="page-header"><div><h1>Editar propiedad</h1></div></div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $property['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e($property['name']) ?>" required></label>
            <label>Tipo
                <select name="type">
                    <?php foreach (['casa','departamento','oficina','local','otro'] as $t): ?>
                        <option value="<?= $t ?>" <?= $property['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Estado
                <select name="status">
                    <?php foreach (['activa','pausada','archivada'] as $s): ?>
                        <option value="<?= $s ?>" <?= $property['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Dirección <input name="address" value="<?= e((string) $property['address']) ?>"></label>
            <label>Moneda <input name="currency" maxlength="3" value="<?= e((string) $property['currency']) ?>"></label>
            <label>m² <input name="area_m2" type="number" step="0.01" value="<?= e((string) $property['area_m2']) ?>"></label>
            <label>Ambientes <input name="rooms" type="number" value="<?= e((string) $property['rooms']) ?>"></label>
            <label>Gestionada desde <input type="date" name="managed_since" value="<?= e((string) $property['managed_since']) ?>"></label>
        </div>
        <label>Descripción <textarea name="description"><?= e((string) $property['description']) ?></textarea></label>
        <label>Notas <textarea name="notes"><?= e((string) $property['notes']) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
        </div>
    </form>
    <form method="post" action="<?= e(url('/properties/' . $property['public_id'] . '/archive')) ?>" onsubmit="return confirm('¿Archivar esta propiedad?');" style="margin-top:1rem">
        <?= csrf_field() ?>
        <button class="btn btn--danger" type="submit">Archivar</button>
    </form>
</section>
