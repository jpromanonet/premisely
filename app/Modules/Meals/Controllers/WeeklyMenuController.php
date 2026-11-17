<?php

declare(strict_types=1);

namespace Premisely\Modules\Meals\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Http\Response;
use Premisely\Shared\PropertyContext;
use Premisely\Shared\SimplePdf;

final class WeeklyMenuController extends Controller
{
    /** @return array<string, string> */
    public static function mealSlots(): array
    {
        return [
            'desayuno' => 'Desayuno',
            'almuerzo' => 'Almuerzo',
            'merienda' => 'Merienda',
            'cena' => 'Cena',
        ];
    }

    /** @return array<string, array{label:string, slots:list<string>}> */
    public static function snackGroups(): array
    {
        return [
            'colaciones' => [
                'label' => 'Colaciones',
                'slots' => ['colacion_1', 'colacion_2', 'colacion_3'],
            ],
            'snacks' => [
                'label' => 'Snacks',
                'slots' => ['snack_1', 'snack_2', 'snack_3'],
            ],
        ];
    }

    /** @return list<string> */
    public static function snackSlotKeys(): array
    {
        $keys = [];
        foreach (self::snackGroups() as $group) {
            foreach ($group['slots'] as $slot) {
                $keys[] = $slot;
            }
        }
        return $keys;
    }

    /** @return array<int, string> */
    public static function weekdays(): array
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    }

    /** @return list<string> */
    public static function categoryColors(): array
    {
        return ['rose', 'sky', 'lime', 'apricot', 'violet', 'teal', 'mustard', 'coral', 'mint', 'indigo'];
    }

    public function index(Request $request, array $params): never
    {
        $this->view('weekly_menu/index', array_merge($this->buildMenuData(), [
            'title' => 'Menú semanal',
            'property' => PropertyContext::property(),
            'canEdit' => PropertyContext::canEdit(),
        ]));
    }

    public function pdf(Request $request, array $params): never
    {
        $data = $this->buildMenuData();
        $property = PropertyContext::property();
        $mealColors = [
            'desayuno' => [0.902, 0.757, 0.353],
            'almuerzo' => [0.490, 0.749, 0.541],
            'merienda' => [0.890, 0.604, 0.416],
            'cena' => [0.608, 0.592, 0.816],
        ];
        $snackAccent = [0.851, 0.420, 0.478];
        $ink = [0.102, 0.180, 0.278];

        $pdf = new SimplePdf();
        $pdf->banner(
            'Menú semanal',
            (string) ($property['name'] ?? 'Premisely') . '  ·  Sin fechas · se repite cada semana',
            'Lunes a domingo'
        );

        foreach (self::weekdays() as $weekday => $dayLabel) {
            $pdf->section($dayLabel, $ink);
            $hasAny = false;
            foreach (self::mealSlots() as $slot => $slotLabel) {
                $meals = $data['byDay'][$weekday][$slot] ?? [];
                if ($meals === []) {
                    continue;
                }
                $hasAny = true;
                $color = $mealColors[$slot] ?? null;
                foreach ($meals as $meal) {
                    $pdf->item((string) $meal['title'], $slotLabel, false, $color);
                }
            }
            if (!$hasAny) {
                $pdf->empty('Sin comidas cargadas.');
            }
        }

        $pdf->divider('Colaciones, snacks, ingredientes y reglas');

        foreach (self::snackGroups() as $group) {
            $pdf->section($group['label'] . ' permitidos', $snackAccent);
            $count = 0;
            foreach ($group['slots'] as $slot) {
                foreach ($data['snacksBySlot'][$slot] ?? [] as $item) {
                    $count++;
                    $pdf->item((string) $item['title'], '', false);
                }
            }
            if ($count === 0) {
                $pdf->empty('Todavía no hay ítems.');
            }
        }

        $pdf->section('Ingredientes', $mealColors['almuerzo']);
        $hasIngredients = false;
        if ($data['uncategorizedIngredients'] !== []) {
            $hasIngredients = true;
            foreach ($data['uncategorizedIngredients'] as $ingredient) {
                $pdf->item((string) $ingredient['title'], '', false);
            }
        }
        foreach ($data['ingredientCategories'] as $category) {
            $catId = (int) $category['id'];
            $group = $data['ingredientsByCategory'][$catId] ?? [];
            if ($group === []) {
                continue;
            }
            $hasIngredients = true;
            foreach ($group as $ingredient) {
                $pdf->item((string) $ingredient['title'], (string) $category['name'], false);
            }
        }
        if (!$hasIngredients) {
            $pdf->empty('Sin ingredientes cargados.');
        }

        $pdf->section('Reglas del menú', $mealColors['cena'], (string) count($data['menuRules']));
        if ($data['menuRules'] === []) {
            $pdf->empty('Sin reglas cargadas.');
        } else {
            foreach ($data['menuRules'] as $i => $rule) {
                $pdf->item((string) $rule['body'], (string) ($i + 1), false);
            }
        }

        $pdf->footer('Premisely · Menú semanal · ' . (string) ($property['name'] ?? ''));
        Response::download($pdf->render(), 'menu-semanal.pdf', 'application/pdf');
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $title = trim((string) $request->input('title', ''));
        $weekday = (int) $request->input('weekday', 0);
        $slot = (string) $request->input('slot', 'almuerzo');
        if ($title === '' || $weekday < 1 || $weekday > 7 || !isset(self::mealSlots()[$slot])) {
            flash('error', 'Completá la comida.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        Connection::query(
            'INSERT INTO weekly_menu_items (public_id, property_id, weekday, slot, title, created_at)
             VALUES (:pid, :prop, :weekday, :slot, :title, :created_at)',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'weekday' => $weekday,
                'slot' => $slot,
                'title' => $title,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        flash('success', 'Comida agregada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
    }

    public function destroy(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $key = (string) ($params['item'] ?? '');
        Connection::query(
            'DELETE FROM weekly_menu_items
             WHERE property_id = :pid AND (public_id = :key OR id = :id)',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        flash('success', 'Comida quitada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
    }

    public function storeSnack(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $title = trim((string) $request->input('title', ''));
        $slot = (string) $request->input('slot', '');
        if ($title === '' || !in_array($slot, self::snackSlotKeys(), true)) {
            flash('error', 'Completá la colación o snack.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#colaciones');
        }
        Connection::query(
            'INSERT INTO weekly_menu_snacks (public_id, property_id, slot, title, created_at)
             VALUES (:pid, :prop, :slot, :title, :created_at)',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'slot' => $slot,
                'title' => $title,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        flash('success', 'Agregado a colaciones.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#colaciones');
    }

    public function destroySnack(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $key = (string) ($params['snack'] ?? '');
        Connection::query(
            'DELETE FROM weekly_menu_snacks
             WHERE property_id = :pid AND (public_id = :key OR id = :id)',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        flash('success', 'Quitado de colaciones.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#colaciones');
    }

    public function storeCategory(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            flash('error', 'Poné un nombre de categoría.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
        }
        $pid = PropertyContext::propertyId();
        $sort = (int) Connection::fetchColumn(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM weekly_menu_ingredient_categories WHERE property_id = :p',
            ['p' => $pid]
        );
        Connection::query(
            'INSERT INTO weekly_menu_ingredient_categories (public_id, property_id, name, color, sort_order, created_at)
             VALUES (:pid, :prop, :name, :color, :sort, :created_at)',
            [
                'pid' => ulid(),
                'prop' => $pid,
                'name' => $name,
                'color' => $this->nextCategoryColor($pid),
                'sort' => $sort,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        flash('success', 'Categoría agregada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
    }

    public function updateCategory(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $key = (string) ($params['category'] ?? '');
        $name = trim((string) $request->input('name', ''));
        if ($name === '' || $key === '') {
            flash('error', 'Poné un nombre de categoría.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
        }
        Connection::query(
            'UPDATE weekly_menu_ingredient_categories
             SET name = :name
             WHERE property_id = :pid AND (public_id = :key OR id = :id)',
            [
                'name' => $name,
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        flash('success', 'Categoría actualizada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
    }

    public function destroyCategory(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $key = (string) ($params['category'] ?? '');
        Connection::query(
            'DELETE FROM weekly_menu_ingredient_categories
             WHERE property_id = :pid AND (public_id = :key OR id = :id)',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        flash('success', 'Categoría quitada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
    }

    public function storeIngredient(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $title = trim((string) $request->input('title', ''));
        $categoryKey = trim((string) $request->input('category', ''));
        if ($title === '' || $categoryKey === '') {
            flash('error', 'Completá el ingrediente y elegí una categoría.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
        }
        $pid = PropertyContext::propertyId();
        $category = Connection::fetch(
            'SELECT id FROM weekly_menu_ingredient_categories
             WHERE property_id = :pid AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => $pid,
                'key' => $categoryKey,
                'id' => ctype_digit($categoryKey) ? (int) $categoryKey : 0,
            ]
        );
        if (!$category) {
            flash('error', 'Elegí una categoría válida.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
        }
        $categoryId = (int) $category['id'];
        $sort = (int) Connection::fetchColumn(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM weekly_menu_ingredients
             WHERE property_id = :p AND category_id = :c',
            ['p' => $pid, 'c' => $categoryId]
        );
        Connection::query(
            'INSERT INTO weekly_menu_ingredients (public_id, property_id, category_id, title, sort_order, created_at)
             VALUES (:pid, :prop, :category_id, :title, :sort, :created_at)',
            [
                'pid' => ulid(),
                'prop' => $pid,
                'category_id' => $categoryId,
                'title' => $title,
                'sort' => $sort,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        flash('success', 'Ingrediente agregado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
    }

    public function destroyIngredient(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $key = (string) ($params['ingredient'] ?? '');
        Connection::query(
            'DELETE FROM weekly_menu_ingredients
             WHERE property_id = :pid AND (public_id = :key OR id = :id)',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        flash('success', 'Ingrediente quitado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#ingredientes');
    }

    public function storeRule(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $body = trim((string) $request->input('body', ''));
        if ($body === '') {
            flash('error', 'Escribí la regla.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#reglas');
        }
        $pid = PropertyContext::propertyId();
        $sort = (int) Connection::fetchColumn(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM weekly_menu_rules WHERE property_id = :p',
            ['p' => $pid]
        );
        Connection::query(
            'INSERT INTO weekly_menu_rules (public_id, property_id, body, sort_order, created_at)
             VALUES (:pid, :prop, :body, :sort, :created_at)',
            [
                'pid' => ulid(),
                'prop' => $pid,
                'body' => mb_substr($body, 0, 500),
                'sort' => $sort,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        flash('success', 'Regla agregada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#reglas');
    }

    public function destroyRule(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu');
        }
        $key = (string) ($params['rule'] ?? '');
        Connection::query(
            'DELETE FROM weekly_menu_rules
             WHERE property_id = :pid AND (public_id = :key OR id = :id)',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        flash('success', 'Regla quitada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/weekly-menu#reglas');
    }

    /** @return array<string, mixed> */
    private function buildMenuData(): array
    {
        $pid = PropertyContext::propertyId();
        $items = Connection::fetchAll(
            'SELECT public_id, weekday, slot, title
             FROM weekly_menu_items
             WHERE property_id = :p
             ORDER BY weekday, FIELD(slot, \'desayuno\', \'almuerzo\', \'merienda\', \'cena\'), id',
            ['p' => $pid]
        );
        $byDay = [];
        foreach ($items as $item) {
            $wd = (int) $item['weekday'];
            $slot = (string) $item['slot'];
            $byDay[$wd][$slot][] = $item;
        }

        $meta = Connection::fetch(
            'SELECT ingredients, rules FROM weekly_menu_meta WHERE property_id = :p LIMIT 1',
            ['p' => $pid]
        ) ?: ['ingredients' => '', 'rules' => ''];

        $this->migrateLegacyIngredients($pid, (string) ($meta['ingredients'] ?? ''));
        $this->migrateLegacyRules($pid, (string) ($meta['rules'] ?? ''));

        $this->ensureCategoryColors($pid);
        $categories = Connection::fetchAll(
            'SELECT id, public_id, name, color
             FROM weekly_menu_ingredient_categories
             WHERE property_id = :p
             ORDER BY sort_order, id',
            ['p' => $pid]
        );
        $ingredients = Connection::fetchAll(
            'SELECT public_id, category_id, title
             FROM weekly_menu_ingredients
             WHERE property_id = :p
             ORDER BY sort_order, id',
            ['p' => $pid]
        );
        $byCategory = [];
        $uncategorized = [];
        foreach ($ingredients as $ingredient) {
            $catId = $ingredient['category_id'] !== null ? (int) $ingredient['category_id'] : 0;
            if ($catId > 0) {
                $byCategory[$catId][] = $ingredient;
            } else {
                $uncategorized[] = $ingredient;
            }
        }

        $this->migrateLegacySnackSlots($pid);

        $slotOrder = implode(', ', array_map(
            static fn (string $s): string => "'" . str_replace("'", "''", $s) . "'",
            self::snackSlotKeys()
        ));
        $snacks = Connection::fetchAll(
            'SELECT public_id, slot, title
             FROM weekly_menu_snacks
             WHERE property_id = :p
             ORDER BY FIELD(slot, ' . $slotOrder . '), id',
            ['p' => $pid]
        );
        $snacksBySlot = [];
        foreach ($snacks as $snack) {
            $snacksBySlot[(string) $snack['slot']][] = $snack;
        }

        $menuRules = Connection::fetchAll(
            'SELECT public_id, body
             FROM weekly_menu_rules
             WHERE property_id = :p
             ORDER BY sort_order, id',
            ['p' => $pid]
        );

        return [
            'byDay' => $byDay,
            'snacksBySlot' => $snacksBySlot,
            'ingredientCategories' => $categories,
            'ingredientsByCategory' => $byCategory,
            'uncategorizedIngredients' => $uncategorized,
            'menuRules' => $menuRules,
            'mealSlots' => self::mealSlots(),
            'snackGroups' => self::snackGroups(),
            'weekdays' => self::weekdays(),
        ];
    }

    private function migrateLegacySnackSlots(int $pid): void
    {
        $map = [
            'frutas' => 'colacion_1',
            'lacteos' => 'colacion_2',
            'frutos_secos' => 'colacion_3',
            'salados' => 'snack_1',
            'dulces' => 'snack_2',
            'bebidas' => 'snack_3',
        ];
        foreach ($map as $from => $to) {
            Connection::query(
                'UPDATE weekly_menu_snacks SET slot = :to
                 WHERE property_id = :p AND slot = :from',
                ['to' => $to, 'p' => $pid, 'from' => $from]
            );
        }
    }

    private function nextCategoryColor(int $pid): string
    {
        $palette = self::categoryColors();
        $used = Connection::fetchAll(
            'SELECT color FROM weekly_menu_ingredient_categories WHERE property_id = :p',
            ['p' => $pid]
        );
        $usedKeys = [];
        foreach ($used as $row) {
            $usedKeys[(string) $row['color']] = true;
        }
        foreach ($palette as $color) {
            if (!isset($usedKeys[$color])) {
                return $color;
            }
        }
        return $palette[count($used) % count($palette)];
    }

    private function ensureCategoryColors(int $pid): void
    {
        $rows = Connection::fetchAll(
            'SELECT id, color FROM weekly_menu_ingredient_categories
             WHERE property_id = :p ORDER BY sort_order, id',
            ['p' => $pid]
        );
        if ($rows === []) {
            return;
        }
        $palette = self::categoryColors();
        $seen = [];
        $needsFix = false;
        foreach ($rows as $row) {
            $color = (string) ($row['color'] ?? '');
            if ($color === '' || !in_array($color, $palette, true) || isset($seen[$color])) {
                $needsFix = true;
                break;
            }
            $seen[$color] = true;
        }
        if (!$needsFix) {
            return;
        }
        foreach ($rows as $i => $row) {
            $pick = $palette[$i % count($palette)];
            if ($pick === (string) ($row['color'] ?? '')) {
                continue;
            }
            Connection::query(
                'UPDATE weekly_menu_ingredient_categories SET color = :color WHERE id = :id AND property_id = :p',
                ['color' => $pick, 'id' => (int) $row['id'], 'p' => $pid]
            );
        }
    }

    private function migrateLegacyIngredients(int $pid, string $legacy): void
    {
        $catCount = (int) Connection::fetchColumn(
            'SELECT COUNT(*) FROM weekly_menu_ingredient_categories WHERE property_id = :p',
            ['p' => $pid]
        );
        $itemCount = (int) Connection::fetchColumn(
            'SELECT COUNT(*) FROM weekly_menu_ingredients WHERE property_id = :p',
            ['p' => $pid]
        );
        $hasStructured = ($catCount + $itemCount) > 0;
        if ($hasStructured || trim($legacy) === '') {
            return;
        }
        $lines = preg_split('/\R/u', $legacy) ?: [];
        $sort = 0;
        foreach ($lines as $line) {
            $title = trim((string) $line);
            if ($title === '') {
                continue;
            }
            $sort++;
            Connection::query(
                'INSERT INTO weekly_menu_ingredients (public_id, property_id, category_id, title, sort_order, created_at)
                 VALUES (:pid, :prop, NULL, :title, :sort, :created_at)',
                [
                    'pid' => ulid(),
                    'prop' => $pid,
                    'title' => mb_substr($title, 0, 200),
                    'sort' => $sort,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );
        }
        Connection::query(
            'UPDATE weekly_menu_meta SET ingredients = NULL, updated_at = :upd WHERE property_id = :p',
            ['upd' => date('Y-m-d H:i:s'), 'p' => $pid]
        );
    }

    private function migrateLegacyRules(int $pid, string $legacy): void
    {
        $count = (int) Connection::fetchColumn(
            'SELECT COUNT(*) FROM weekly_menu_rules WHERE property_id = :p',
            ['p' => $pid]
        );
        if ($count > 0 || trim($legacy) === '') {
            return;
        }
        $lines = preg_split('/\R/u', $legacy) ?: [];
        $sort = 0;
        foreach ($lines as $line) {
            $body = trim((string) $line);
            $body = preg_replace('/^\d+[\).\:\-]\s*/u', '', $body) ?? $body;
            if ($body === '') {
                continue;
            }
            $sort++;
            Connection::query(
                'INSERT INTO weekly_menu_rules (public_id, property_id, body, sort_order, created_at)
                 VALUES (:pid, :prop, :body, :sort, :created_at)',
                [
                    'pid' => ulid(),
                    'prop' => $pid,
                    'body' => mb_substr($body, 0, 500),
                    'sort' => $sort,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );
        }
        Connection::query(
            'UPDATE weekly_menu_meta SET rules = NULL, updated_at = :upd WHERE property_id = :p',
            ['upd' => date('Y-m-d H:i:s'), 'p' => $pid]
        );
    }
}
