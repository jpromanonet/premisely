<?php
/** @var array<string,mixed> $property */
/** @var array<int, array<string, list<array<string,mixed>>>> $byDay */
/** @var array<string, list<array<string,mixed>>> $snacksBySlot */
/** @var list<array<string,mixed>> $ingredientCategories */
/** @var array<int, list<array<string,mixed>>> $ingredientsByCategory */
/** @var list<array<string,mixed>> $uncategorizedIngredients */
/** @var array<string, string> $mealSlots */
/** @var array<string, array{label:string, slots:list<string>}> $snackGroups */
/** @var list<array{title:string, tone:string}> $fridayPermitted */
/** @var array<int, string> $weekdays */
/** @var list<array<string,mixed>> $menuRules */
/** @var bool $canEdit */
$pid = $property['public_id'];
$byDay = $byDay ?? [];
$snacksBySlot = $snacksBySlot ?? [];
$ingredientCategories = $ingredientCategories ?? [];
$ingredientsByCategory = $ingredientsByCategory ?? [];
$uncategorizedIngredients = $uncategorizedIngredients ?? [];
$menuRules = $menuRules ?? [];
$weekdays = $weekdays ?? [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
$slots = $mealSlots ?? ['desayuno' => 'Desayuno', 'almuerzo' => 'Almuerzo', 'merienda' => 'Merienda', 'cena' => 'Cena'];
$snackGroups = $snackGroups ?? [
    'colaciones' => ['label' => 'Colaciones', 'slots' => ['colacion_1', 'colacion_2', 'colacion_3']],
    'snacks' => ['label' => 'Snacks', 'slots' => ['snack_1', 'snack_2', 'snack_3']],
];
$fridayPermitted = $fridayPermitted ?? [
    ['title' => 'Picada (salame, queso, twistos, papitas, aceitunas)', 'tone' => 'coral'],
    ['title' => 'Vino tinto', 'tone' => 'violet'],
    ['title' => '1 Kg de helado', 'tone' => 'sky'],
];
$snackTone = [
    'colacion_1' => 'rose',
    'colacion_2' => 'sky',
    'colacion_3' => 'lime',
    'snack_1' => 'apricot',
    'snack_2' => 'violet',
    'snack_3' => 'teal',
];
$hasIngredients = $ingredientCategories !== [] || $uncategorizedIngredients !== [];
foreach ($ingredientsByCategory as $group) {
    if ($group !== []) {
        $hasIngredients = true;
        break;
    }
}
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Sin fechas · se repite cada semana</p>
        <h1>Menú semanal</h1>
        <p>Las cuatro comidas de lunes a domingo.</p>
    </div>
    <div class="actions">
        <a class="btn" href="<?= e(url('/properties/' . $pid . '/weekly-menu/pdf')) ?>">Exportar PDF</a>
    </div>
</div>

<div class="weekly-menu-grid">
    <?php foreach ($weekdays as $weekday => $dayLabel): ?>
        <section class="weekly-menu-day">
            <h2><?= e($dayLabel) ?></h2>
            <?php foreach ($slots as $slot => $slotLabel): ?>
                <article class="meal-card meal-card--<?= e($slot) ?>">
                    <header><?= e($slotLabel) ?></header>
                    <?php foreach ($byDay[$weekday][$slot] ?? [] as $meal): ?>
                        <div class="meal-item">
                            <strong><?= e((string) $meal['title']) ?></strong>
                            <?php if (!empty($canEdit)): ?>
                                <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/' . $meal['public_id'] . '/delete')) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" title="Quitar">×</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!empty($canEdit)): ?>
                        <form class="meal-add" method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="weekday" value="<?= (int) $weekday ?>">
                            <input type="hidden" name="slot" value="<?= e($slot) ?>">
                            <input name="title" placeholder="Agregar" required>
                            <button type="submit">+</button>
                        </form>
                    <?php elseif (($byDay[$weekday][$slot] ?? []) === []): ?>
                        <p class="muted">Sin definir</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>

<hr class="weekly-menu-divider">
<section class="weekly-friday" id="viernes">
    <h2 class="weekly-snacks__title">Permitido semanal de Viernes</h2>
    <div class="weekly-snacks-grid">
        <?php foreach ($fridayPermitted as $item): ?>
            <article class="meal-card meal-card--fixed meal-card--snack-<?= e((string) $item['tone']) ?>">
                <strong><?= e((string) $item['title']) ?></strong>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<hr class="weekly-menu-divider">
<section class="weekly-snacks" id="colaciones">
    <h2 class="weekly-snacks__title">Colaciones y Snacks permitidos</h2>
    <?php foreach ($snackGroups as $groupKey => $group): ?>
        <div class="weekly-snacks-block">
            <h3 class="weekly-snacks__subtitle"><?= e((string) $group['label']) ?></h3>
            <div class="weekly-snacks-grid">
                <?php foreach ($group['slots'] as $slot): ?>
                    <?php $tone = $snackTone[$slot] ?? 'rose'; ?>
                    <article class="meal-card meal-card--snack-<?= e($tone) ?>">
                        <?php foreach ($snacksBySlot[$slot] ?? [] as $snack): ?>
                            <div class="meal-item">
                                <strong><?= e((string) $snack['title']) ?></strong>
                                <?php if (!empty($canEdit)): ?>
                                    <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/snacks/' . $snack['public_id'] . '/delete')) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" title="Quitar">×</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!empty($canEdit) && ($snacksBySlot[$slot] ?? []) === []): ?>
                            <form class="meal-add" method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/snacks')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="slot" value="<?= e($slot) ?>">
                                <input name="title" placeholder="Agregar" required>
                                <button type="submit">+</button>
                            </form>
                        <?php elseif (($snacksBySlot[$slot] ?? []) === []): ?>
                            <p class="muted">Sin definir</p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<div class="weekly-menu-meta">
    <article class="weekly-menu-meta-card" id="ingredientes">
        <div class="section-head">
            <h2>Ingredientes</h2>
            <?php if (!empty($canEdit)): ?>
                <div class="section-actions">
                    <button type="button" class="btn btn--sm btn--ghost" data-modal-open="ingredient-category-modal">+ Categoría</button>
                    <?php if ($ingredientCategories !== []): ?>
                        <button type="button" class="btn btn--sm" data-modal-open="ingredient-item-modal">Agregar</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($ingredientCategories === [] && empty($canEdit)): ?>
            <p class="empty-note">Todavía no cargaste ingredientes.</p>
        <?php elseif ($ingredientCategories === [] && !empty($canEdit)): ?>
            <p class="empty-note">Primero creá una categoría para poder agregar ingredientes.</p>
        <?php elseif (!$hasIngredients): ?>
            <p class="empty-note">Todavía no cargaste ingredientes.</p>
        <?php endif; ?>

        <?php if ($uncategorizedIngredients !== []): ?>
            <div class="ingredient-group ingredient-group--plain">
                <h3 class="ingredient-group__title">Sin categoría</h3>
                <p class="muted">Estos hay que moverlos a una categoría o sacarlos.</p>
                <?php foreach ($uncategorizedIngredients as $ingredient): ?>
                    <div class="meal-item">
                        <strong><?= e((string) $ingredient['title']) ?></strong>
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/ingredients/' . $ingredient['public_id'] . '/delete')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" title="Quitar">×</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php foreach ($ingredientCategories as $category): ?>
            <?php
            $catId = (int) $category['id'];
            $catColor = (string) ($category['color'] ?? 'rose');
            $catModal = 'ingredient-category-edit-' . $category['public_id'];
            ?>
            <div class="ingredient-group ingredient-group--<?= e($catColor) ?>">
                <div class="ingredient-group__head">
                    <h3 class="ingredient-group__title"><?= e((string) $category['name']) ?></h3>
                    <?php if (!empty($canEdit)): ?>
                        <div class="ingredient-group__actions">
                            <button type="button" class="ingredient-group__edit" data-modal-open="<?= e($catModal) ?>" title="Editar nombre">✎</button>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/categories/' . $category['public_id'] . '/delete')) ?>" onsubmit="return confirm('¿Quitar esta categoría? Los ingredientes quedan sin categoría.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="ingredient-group__remove" title="Quitar categoría">×</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (($ingredientsByCategory[$catId] ?? []) === []): ?>
                    <p class="muted">Sin ingredientes</p>
                <?php endif; ?>
                <?php foreach ($ingredientsByCategory[$catId] ?? [] as $ingredient): ?>
                    <div class="meal-item">
                        <strong><?= e((string) $ingredient['title']) ?></strong>
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/ingredients/' . $ingredient['public_id'] . '/delete')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" title="Quitar">×</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </article>

    <article class="weekly-menu-meta-card" id="reglas">
        <div class="section-head">
            <h2>Reglas del menú</h2>
            <?php if (!empty($canEdit)): ?>
                <button type="button" class="btn btn--sm" data-modal-open="rule-add-modal">Agregar</button>
            <?php endif; ?>
        </div>
        <?php if ($menuRules === []): ?>
            <p class="empty-note">Todavía no cargaste reglas.</p>
        <?php else: ?>
            <?php
            $ruleTones = ['rose', 'sky', 'lime', 'apricot', 'violet', 'teal', 'mustard', 'coral', 'mint', 'indigo'];
            ?>
            <div class="menu-rules-list">
                <?php foreach ($menuRules as $i => $rule): ?>
                    <?php $tone = $ruleTones[$i % count($ruleTones)]; ?>
                    <article class="menu-rule-card menu-rule-card--<?= e($tone) ?>">
                        <span class="menu-rule-card__num"><?= (int) $i + 1 ?></span>
                        <p class="menu-rule-card__body"><?= e((string) $rule['body']) ?></p>
                        <?php if (!empty($canEdit)): ?>
                            <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/rules/' . $rule['public_id'] . '/delete')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" title="Quitar">×</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</div>

<?php if (!empty($canEdit)): ?>
<?php if ($ingredientCategories !== []): ?>
<div class="modal" id="ingredient-item-modal" hidden>
    <button type="button" class="modal__backdrop" data-modal-close aria-label="Cerrar"></button>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="ingredient-item-title">
        <div class="modal__head">
            <h2 id="ingredient-item-title">Agregar ingrediente</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">×</button>
        </div>
        <p class="muted">Sumá lo que hace falta para armar la semana.</p>
        <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/ingredients')) ?>" class="stack">
            <?= csrf_field() ?>
            <label>Ingrediente
                <input name="title" placeholder="Ej. huevos, leche, pollo..." required>
            </label>
            <label>Categoría
                <select name="category" required>
                    <option value="" disabled selected>Elegí una categoría</option>
                    <?php foreach ($ingredientCategories as $category): ?>
                        <option value="<?= e((string) $category['public_id']) ?>"><?= e((string) $category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="actions">
                <button class="btn" type="submit">Agregar</button>
                <button class="btn btn--ghost" type="button" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="modal" id="ingredient-category-modal" hidden>
    <button type="button" class="modal__backdrop" data-modal-close aria-label="Cerrar"></button>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="ingredient-category-title">
        <div class="modal__head">
            <h2 id="ingredient-category-title">Nueva categoría</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">×</button>
        </div>
        <p class="muted">Sirve de separador para agrupar ingredientes. Cada una sale con un color distinto.</p>
        <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/categories')) ?>" class="stack">
            <?= csrf_field() ?>
            <label>Nombre
                <input name="name" placeholder="Ej. Verduras, Carnes, Lácteos..." required>
            </label>
            <div class="actions">
                <button class="btn" type="submit">Crear categoría</button>
                <button class="btn btn--ghost" type="button" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($ingredientCategories as $category): ?>
    <?php $catModal = 'ingredient-category-edit-' . $category['public_id']; ?>
    <div class="modal" id="<?= e($catModal) ?>" hidden>
        <button type="button" class="modal__backdrop" data-modal-close aria-label="Cerrar"></button>
        <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="<?= e($catModal) ?>-title">
            <div class="modal__head">
                <h2 id="<?= e($catModal) ?>-title">Editar categoría</h2>
                <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">×</button>
            </div>
            <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/categories/' . $category['public_id'])) ?>" class="stack">
                <?= csrf_field() ?>
                <label>Nombre
                    <input name="name" value="<?= e((string) $category['name']) ?>" required>
                </label>
                <div class="actions">
                    <button class="btn" type="submit">Guardar</button>
                    <button class="btn btn--ghost" type="button" data-modal-close>Cancelar</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal" id="rule-add-modal" hidden>
    <button type="button" class="modal__backdrop" data-modal-close aria-label="Cerrar"></button>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="rule-add-title">
        <div class="modal__head">
            <h2 id="rule-add-title">Agregar regla</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">×</button>
        </div>
        <p class="muted">Una regla por vez. Se numera sola en la lista.</p>
        <form method="post" action="<?= e(url('/properties/' . $pid . '/weekly-menu/rules')) ?>" class="stack">
            <?= csrf_field() ?>
            <label>Regla
                <input name="body" placeholder="Ej. Un día vegetariano por semana" required maxlength="500">
            </label>
            <div class="actions">
                <button class="btn" type="submit">Agregar</button>
                <button class="btn btn--ghost" type="button" data-modal-close>Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
