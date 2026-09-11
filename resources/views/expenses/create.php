<?php
/** @var array<string,mixed> $property */
/** @var list<array<string,mixed>> $categories */
$pid = $property['public_id'];
?>
<div class="page-header">
    <div>
        <h1>Nuevo gasto</h1>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">← Volver</a>
</div>
<section class="panel">
    <form method="post" action="<?= e(url('/properties/' . $pid . '/expenses')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Título <input name="title" required></label>
            <label>Monto <input type="number" step="0.01" name="amount" required></label>
            <label>Fecha <input type="date" name="spent_at" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Moneda <input name="currency" maxlength="3" value="<?= e((string)($property['currency'] ?? 'ARS')) ?>"></label>
            <label>Categoría
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Notas <textarea name="notes"></textarea></label>
        <div class="actions">
            <button class="btn" type="submit">Registrar</button>
            <a class="btn btn--ghost" href="<?= e(url('/properties/' . $pid . '/expenses')) ?>">Cancelar</a>
        </div>
    </form>
</section>
