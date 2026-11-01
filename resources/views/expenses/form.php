<?php
/** @var array<string,mixed> $property */
/** @var array<string,mixed> $expense */
/** @var list<array<string,mixed>> $categories */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div><h1>Editar gasto</h1></div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses/' . $expense['public_id'])) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" value="<?= e((string) $expense['title']) ?>" required></label>
            <label>Monto <input type="number" step="0.01" name="amount" value="<?= e((string) $expense['amount']) ?>" required></label>
            <label>Fecha <input type="date" name="spent_at" value="<?= e((string) $expense['spent_at']) ?>" required></label>
            <label>Moneda <input name="currency" maxlength="3" value="<?= e((string) ($expense['currency'] ?? $property['currency'] ?? 'ARS')) ?>"></label>
            <label>Categoría
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ((int) ($expense['category_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Notas <textarea name="notes"><?= e((string) ($expense['notes'] ?? '')) ?></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">Cancelar</a>
        </div>
    </form>
</section>
