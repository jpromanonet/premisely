<div class="page-header"><div><h1>Nueva propiedad</h1></div></div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Nombre <input name="name" value="<?= e((string) old('name')) ?>" required></label>
            <label>Tipo
                <select name="type">
                    <?php foreach (['casa','departamento','oficina','local','otro'] as $t): ?>
                        <option value="<?= $t ?>" <?= old('type') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Dirección <input name="address" value="<?= e((string) old('address')) ?>"></label>
            <label>Moneda <input name="currency" maxlength="3" value="<?= e((string) (old('currency') ?: 'ARS')) ?>"></label>
            <label>m² <input name="area_m2" type="number" step="0.01" value="<?= e((string) old('area_m2')) ?>"></label>
            <label>Ambientes <input name="rooms" type="number" value="<?= e((string) old('rooms')) ?>"></label>
        </div>
        <label>Descripción <textarea name="description"><?= e((string) old('description')) ?></textarea></label>
        <label>Notas <textarea name="notes"><?= e((string) old('notes')) ?></textarea></label>
        <button class="btn" type="submit">Crear</button>
    </form>
</section>
