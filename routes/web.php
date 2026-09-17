<?php

declare(strict_types=1);

use Premisely\Core\Middleware\AuthMiddleware;
use Premisely\Core\Middleware\CsrfMiddleware;
use Premisely\Core\Middleware\PropertyAccessMiddleware;
use Premisely\Modules\Accounts\Controllers\AuthController;
use Premisely\Modules\Accounts\Controllers\SettingsController;
use Premisely\Modules\Cleaning\Controllers\CleaningController;
use Premisely\Modules\Dashboard\Controllers\DashboardController;
use Premisely\Modules\Documents\Controllers\DocumentController;
use Premisely\Modules\Expenses\Controllers\ExpenseController;
use Premisely\Modules\Files\Controllers\FileController;
use Premisely\Modules\Install\Controllers\InstallController;
use Premisely\Modules\Inventory\Controllers\InventoryController;
use Premisely\Modules\Maintenance\Controllers\MaintenanceController;
use Premisely\Modules\Members\Controllers\MemberController;
use Premisely\Modules\Properties\Controllers\PropertyController;
use Premisely\Modules\Repairs\Controllers\RepairController;
use Premisely\Modules\Routines\Controllers\RoutineController;
use Premisely\Modules\Search\Controllers\SearchController;
use Premisely\Modules\Services\Controllers\ServiceController;
use Premisely\Modules\Shopping\Controllers\ShoppingController;
use Premisely\Modules\Spaces\Controllers\SpaceController;
use Premisely\Modules\Stock\Controllers\StockController;
use Premisely\Modules\Tasks\Controllers\TaskController;

/** @var \Premisely\Core\Routing\Router $router */

$auth = [AuthMiddleware::class];
$csrf = [CsrfMiddleware::class];
$authCsrf = [AuthMiddleware::class, CsrfMiddleware::class];
$property = [AuthMiddleware::class, PropertyAccessMiddleware::class];
$propertyCsrf = [AuthMiddleware::class, CsrfMiddleware::class, PropertyAccessMiddleware::class];

// Home
$router->get('/', static function () {
    if (\Premisely\Core\Auth\Auth::check()) {
        redirect('/dashboard');
    }
    redirect('/login');
});

// Install (no auth)
$router->get('/install', [InstallController::class, 'index']);
$router->get('/install/requirements', [InstallController::class, 'requirements']);
$router->get('/install/database', [InstallController::class, 'databaseForm']);
$router->post('/install/database', [InstallController::class, 'database'], $csrf);
$router->get('/install/admin', [InstallController::class, 'adminForm']);
$router->post('/install/admin', [InstallController::class, 'admin'], $csrf);
$router->get('/install/finish', [InstallController::class, 'finishForm']);
$router->post('/install/finish', [InstallController::class, 'finish'], $csrf);

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], $csrf);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register'], $csrf);
$router->post('/logout', [AuthController::class, 'logout'], $authCsrf);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot'], $csrf);
$router->get('/profile', [AuthController::class, 'profile'], $auth);
$router->post('/profile', [AuthController::class, 'updateProfile'], $authCsrf);
$router->get('/settings', [SettingsController::class, 'edit'], $auth);
$router->post('/settings', [SettingsController::class, 'update'], $authCsrf);
$router->get('/settings/profile', [SettingsController::class, 'profile'], $auth);
$router->post('/settings/profile', [SettingsController::class, 'updateProfile'], $authCsrf);
$router->post('/settings/password', [SettingsController::class, 'updatePassword'], $authCsrf);

// Global dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], $auth);

// Properties
$router->get('/properties', [PropertyController::class, 'index'], $auth);
$router->get('/properties/create', [PropertyController::class, 'create'], $auth);
$router->post('/properties', [PropertyController::class, 'store'], $authCsrf);
$router->get('/properties/{property}', [PropertyController::class, 'show'], $property);
$router->get('/properties/{property}/edit', [PropertyController::class, 'edit'], $property);
$router->post('/properties/{property}', [PropertyController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/archive', [PropertyController::class, 'archive'], $propertyCsrf);
$router->post('/properties/{property}/delete', [PropertyController::class, 'destroy'], $propertyCsrf);
$router->get('/properties/{property}/dashboard', [DashboardController::class, 'property'], $property);

// Members
$router->get('/properties/{property}/members', [MemberController::class, 'index'], $property);
$router->get('/properties/{property}/members/create', [MemberController::class, 'create'], $property);
$router->post('/properties/{property}/members', [MemberController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/members/leave', [MemberController::class, 'leave'], $propertyCsrf);
$router->post('/properties/{property}/members/{member}', [MemberController::class, 'update'], $propertyCsrf);

// Spaces
$router->get('/properties/{property}/spaces', [SpaceController::class, 'index'], $property);
$router->get('/properties/{property}/spaces/create', [SpaceController::class, 'create'], $property);
$router->post('/properties/{property}/spaces', [SpaceController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/spaces/{space}/edit', [SpaceController::class, 'edit'], $property);
$router->post('/properties/{property}/spaces/{space}', [SpaceController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/spaces/{space}/archive', [SpaceController::class, 'archive'], $propertyCsrf);

// Inventory
$router->get('/properties/{property}/inventory', [InventoryController::class, 'index'], $property);
$router->get('/properties/{property}/inventory/create', [InventoryController::class, 'create'], $property);
$router->post('/properties/{property}/inventory', [InventoryController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/inventory/{item}', [InventoryController::class, 'show'], $property);
$router->get('/properties/{property}/inventory/{item}/edit', [InventoryController::class, 'edit'], $property);
$router->post('/properties/{property}/inventory/{item}', [InventoryController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/inventory/{item}/move', [InventoryController::class, 'move'], $propertyCsrf);
$router->post('/properties/{property}/inventory/{item}/archive', [InventoryController::class, 'archive'], $propertyCsrf);

// Stock
$router->get('/properties/{property}/stock', [StockController::class, 'index'], $property);
$router->get('/properties/{property}/stock/create', [StockController::class, 'create'], $property);
$router->post('/properties/{property}/stock', [StockController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/stock/{item}', [StockController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/stock/{item}/adjust', [StockController::class, 'adjust'], $propertyCsrf);
$router->post('/properties/{property}/stock/{item}/archive', [StockController::class, 'archive'], $propertyCsrf);

// Shopping
$router->get('/properties/{property}/shopping', [ShoppingController::class, 'index'], $property);
$router->get('/properties/{property}/shopping/create', [ShoppingController::class, 'create'], $property);
$router->post('/properties/{property}/shopping', [ShoppingController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/shopping/{item}/complete', [ShoppingController::class, 'complete'], $propertyCsrf);
$router->post('/properties/{property}/shopping/{item}/delete', [ShoppingController::class, 'delete'], $propertyCsrf);

// Tasks
$router->get('/properties/{property}/tasks', [TaskController::class, 'index'], $property);
$router->get('/properties/{property}/tasks/create', [TaskController::class, 'create'], $property);
$router->post('/properties/{property}/tasks', [TaskController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/tasks/{task}/edit', [TaskController::class, 'edit'], $property);
$router->post('/properties/{property}/tasks/{task}', [TaskController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/tasks/{task}/status', [TaskController::class, 'updateStatus'], $propertyCsrf);
$router->post('/properties/{property}/tasks/{task}/archive', [TaskController::class, 'archive'], $propertyCsrf);

// Routines
$router->get('/properties/{property}/routines', [RoutineController::class, 'index'], $property);
$router->get('/properties/{property}/routines/create', [RoutineController::class, 'create'], $property);
$router->post('/properties/{property}/routines', [RoutineController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/routines/{routine}/edit', [RoutineController::class, 'edit'], $property);
$router->post('/properties/{property}/routines/{routine}', [RoutineController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/routines/{routine}/execute', [RoutineController::class, 'execute'], $propertyCsrf);
$router->post('/properties/{property}/routines/{routine}/toggle', [RoutineController::class, 'toggleActive'], $propertyCsrf);
$router->post('/properties/{property}/routines/{routine}/delete', [RoutineController::class, 'archive'], $propertyCsrf);

// Cleaning
$router->get('/properties/{property}/cleaning', [CleaningController::class, 'index'], $property);
$router->get('/properties/{property}/cleaning/create', [CleaningController::class, 'create'], $property);
$router->post('/properties/{property}/cleaning', [CleaningController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/cleaning/{routine}/edit', [CleaningController::class, 'edit'], $property);
$router->post('/properties/{property}/cleaning/{routine}', [CleaningController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/cleaning/{routine}/execute', [CleaningController::class, 'execute'], $propertyCsrf);
$router->post('/properties/{property}/cleaning/{routine}/toggle', [CleaningController::class, 'toggle'], $propertyCsrf);
$router->post('/properties/{property}/cleaning/{routine}/delete', [CleaningController::class, 'destroy'], $propertyCsrf);

// Maintenance
$router->get('/properties/{property}/maintenance', [MaintenanceController::class, 'index'], $property);
$router->get('/properties/{property}/maintenance/plans/create', [MaintenanceController::class, 'createPlan'], $property);
$router->post('/properties/{property}/maintenance/plans', [MaintenanceController::class, 'storePlan'], $propertyCsrf);
$router->get('/properties/{property}/maintenance/plans/{plan}/edit', [MaintenanceController::class, 'editPlan'], $property);
$router->post('/properties/{property}/maintenance/plans/{plan}', [MaintenanceController::class, 'updatePlan'], $propertyCsrf);
$router->post('/properties/{property}/maintenance/plans/{plan}/delete', [MaintenanceController::class, 'destroyPlan'], $propertyCsrf);
$router->get('/properties/{property}/maintenance/records/create', [MaintenanceController::class, 'createRecord'], $property);
$router->post('/properties/{property}/maintenance/records', [MaintenanceController::class, 'storeRecord'], $propertyCsrf);
$router->post('/properties/{property}/maintenance/records/{record}/delete', [MaintenanceController::class, 'destroyRecord'], $propertyCsrf);

// Repairs
$router->get('/properties/{property}/repairs', [RepairController::class, 'index'], $property);
$router->get('/properties/{property}/repairs/create', [RepairController::class, 'create'], $property);
$router->post('/properties/{property}/repairs', [RepairController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/repairs/{repair}/edit', [RepairController::class, 'edit'], $property);
$router->post('/properties/{property}/repairs/{repair}/edit', [RepairController::class, 'save'], $propertyCsrf);
$router->post('/properties/{property}/repairs/{repair}', [RepairController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/repairs/{repair}/close', [RepairController::class, 'close'], $propertyCsrf);
$router->post('/properties/{property}/repairs/{repair}/delete', [RepairController::class, 'destroy'], $propertyCsrf);

// Services
$router->get('/properties/{property}/services', [ServiceController::class, 'index'], $property);
$router->get('/properties/{property}/services/create', [ServiceController::class, 'create'], $property);
$router->post('/properties/{property}/services', [ServiceController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/services/{service}/edit', [ServiceController::class, 'edit'], $property);
$router->post('/properties/{property}/services/{service}', [ServiceController::class, 'update'], $propertyCsrf);
$router->get('/properties/{property}/services/{service}/bills/create', [ServiceController::class, 'createBill'], $property);
$router->post('/properties/{property}/services/{service}/bills', [ServiceController::class, 'addBill'], $propertyCsrf);
$router->post('/properties/{property}/services/{service}/status', [ServiceController::class, 'updateStatus'], $propertyCsrf);
$router->post('/properties/{property}/services/{service}/delete', [ServiceController::class, 'destroy'], $propertyCsrf);
$router->post('/properties/{property}/services/bills/{bill}/delete', [ServiceController::class, 'destroyBill'], $propertyCsrf);

// Expenses
$router->get('/properties/{property}/expenses', [ExpenseController::class, 'index'], $property);
$router->get('/properties/{property}/expenses/create', [ExpenseController::class, 'create'], $property);
$router->post('/properties/{property}/expenses', [ExpenseController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/expenses/{expense}/archive', [ExpenseController::class, 'archive'], $propertyCsrf);

// Documents
$router->get('/properties/{property}/documents', [DocumentController::class, 'index'], $property);
$router->get('/properties/{property}/documents/create', [DocumentController::class, 'create'], $property);
$router->post('/properties/{property}/documents', [DocumentController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/documents/{document}/archive', [DocumentController::class, 'archive'], $propertyCsrf);

// Search
$router->get('/properties/{property}/search', [SearchController::class, 'index'], $property);

// V1.5 — Providers
$router->get('/properties/{property}/providers', [\Premisely\Modules\Providers\Controllers\ProviderController::class, 'index'], $property);
$router->get('/properties/{property}/providers/create', [\Premisely\Modules\Providers\Controllers\ProviderController::class, 'create'], $property);
$router->post('/properties/{property}/providers', [\Premisely\Modules\Providers\Controllers\ProviderController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/providers/{provider}/edit', [\Premisely\Modules\Providers\Controllers\ProviderController::class, 'edit'], $property);
$router->post('/properties/{property}/providers/{provider}', [\Premisely\Modules\Providers\Controllers\ProviderController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/providers/{provider}/archive', [\Premisely\Modules\Providers\Controllers\ProviderController::class, 'archive'], $propertyCsrf);

// V1.5 — Boxes
$router->get('/properties/{property}/boxes', [\Premisely\Modules\Boxes\Controllers\BoxController::class, 'index'], $property);
$router->get('/properties/{property}/boxes/create', [\Premisely\Modules\Boxes\Controllers\BoxController::class, 'create'], $property);
$router->post('/properties/{property}/boxes', [\Premisely\Modules\Boxes\Controllers\BoxController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/boxes/{box}', [\Premisely\Modules\Boxes\Controllers\BoxController::class, 'show'], $property);
$router->post('/properties/{property}/boxes/{box}/items', [\Premisely\Modules\Boxes\Controllers\BoxController::class, 'addItem'], $propertyCsrf);
$router->post('/properties/{property}/boxes/{box}/archive', [\Premisely\Modules\Boxes\Controllers\BoxController::class, 'archive'], $propertyCsrf);

// V1.5 — Meals
$router->get('/properties/{property}/meals', [\Premisely\Modules\Meals\Controllers\MealController::class, 'index'], $property);
$router->get('/properties/{property}/meals/create', [\Premisely\Modules\Meals\Controllers\MealController::class, 'create'], $property);
$router->post('/properties/{property}/meals', [\Premisely\Modules\Meals\Controllers\MealController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/meals/{meal}/edit', [\Premisely\Modules\Meals\Controllers\MealController::class, 'edit'], $property);
$router->post('/properties/{property}/meals/{meal}', [\Premisely\Modules\Meals\Controllers\MealController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/meals/{meal}/delete', [\Premisely\Modules\Meals\Controllers\MealController::class, 'destroy'], $propertyCsrf);

// V1.5 — Clothing
$router->get('/properties/{property}/clothing', [\Premisely\Modules\Clothing\Controllers\ClothingController::class, 'index'], $property);
$router->get('/properties/{property}/clothing/create', [\Premisely\Modules\Clothing\Controllers\ClothingController::class, 'create'], $property);
$router->post('/properties/{property}/clothing', [\Premisely\Modules\Clothing\Controllers\ClothingController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/clothing/{item}/archive', [\Premisely\Modules\Clothing\Controllers\ClothingController::class, 'archive'], $propertyCsrf);

// V1.5 — Laundry
$router->get('/properties/{property}/laundry', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'index'], $property);
$router->get('/properties/{property}/laundry/create', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'create'], $property);
$router->post('/properties/{property}/laundry', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/laundry/{routine}/edit', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'edit'], $property);
$router->post('/properties/{property}/laundry/{routine}', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/laundry/{routine}/execute', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'execute'], $propertyCsrf);
$router->post('/properties/{property}/laundry/{routine}/toggle', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'toggle'], $propertyCsrf);
$router->post('/properties/{property}/laundry/{routine}/delete', [\Premisely\Modules\Laundry\Controllers\LaundryController::class, 'destroy'], $propertyCsrf);

// V1.5 — Calendar / Planning / Reports / QR / Warranties
$router->get('/properties/{property}/calendar', [\Premisely\Modules\Calendar\Controllers\CalendarController::class, 'index'], $property);
$router->get('/properties/{property}/calendar/notes/create', [\Premisely\Modules\Calendar\Controllers\CalendarController::class, 'createNote'], $property);
$router->post('/properties/{property}/calendar/notes', [\Premisely\Modules\Calendar\Controllers\CalendarController::class, 'storeNote'], $propertyCsrf);
$router->get('/properties/{property}/calendar/notes/{note}/edit', [\Premisely\Modules\Calendar\Controllers\CalendarController::class, 'editNote'], $property);
$router->post('/properties/{property}/calendar/notes/{note}', [\Premisely\Modules\Calendar\Controllers\CalendarController::class, 'updateNote'], $propertyCsrf);
$router->post('/properties/{property}/calendar/notes/{note}/delete', [\Premisely\Modules\Calendar\Controllers\CalendarController::class, 'destroyNote'], $propertyCsrf);
$router->get('/properties/{property}/planning', [\Premisely\Modules\Planning\Controllers\PlanningController::class, 'weekly'], $property);
$router->get('/properties/{property}/reports', [\Premisely\Modules\Reports\Controllers\ReportController::class, 'index'], $property);
$router->get('/properties/{property}/inventory/{item}/qr', [\Premisely\Modules\Qr\Controllers\QrController::class, 'show'], $property);
$router->post('/properties/{property}/inventory/{item}/warranties', [\Premisely\Modules\Inventory\Controllers\WarrantyController::class, 'store'], $propertyCsrf);

// V2 — Moves
$router->get('/properties/{property}/moves', [\Premisely\Modules\Moves\Controllers\MoveController::class, 'index'], $property);
$router->get('/properties/{property}/moves/create', [\Premisely\Modules\Moves\Controllers\MoveController::class, 'create'], $property);
$router->post('/properties/{property}/moves', [\Premisely\Modules\Moves\Controllers\MoveController::class, 'store'], $propertyCsrf);
$router->get('/properties/{property}/moves/{move}', [\Premisely\Modules\Moves\Controllers\MoveController::class, 'show'], $property);
$router->post('/properties/{property}/moves/{move}/items', [\Premisely\Modules\Moves\Controllers\MoveController::class, 'addItem'], $propertyCsrf);
$router->post('/properties/{property}/moves/{move}/apply', [\Premisely\Modules\Moves\Controllers\MoveController::class, 'apply'], $propertyCsrf);

// V2 — Automations
$router->get('/properties/{property}/automations', [\Premisely\Modules\Automations\Controllers\AutomationController::class, 'index'], $property);
$router->get('/properties/{property}/automations/create', [\Premisely\Modules\Automations\Controllers\AutomationController::class, 'create'], $property);
$router->post('/properties/{property}/automations', [\Premisely\Modules\Automations\Controllers\AutomationController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/automations/run', [\Premisely\Modules\Automations\Controllers\AutomationController::class, 'run'], $propertyCsrf);
$router->post('/properties/{property}/automations/{rule}/toggle', [\Premisely\Modules\Automations\Controllers\AutomationController::class, 'toggle'], $propertyCsrf);
$router->post('/properties/{property}/automations/{rule}/delete', [\Premisely\Modules\Automations\Controllers\AutomationController::class, 'destroy'], $propertyCsrf);

// V2 — Consumption / Suggestions
$router->get('/properties/{property}/consumption', [\Premisely\Modules\Consumption\Controllers\ConsumptionController::class, 'index'], $property);
$router->get('/properties/{property}/suggestions', [\Premisely\Modules\Suggestions\Controllers\SuggestionController::class, 'index'], $property);

// V2 — Import / Export
$router->get('/properties/{property}/importexport', [\Premisely\Modules\ImportExport\Controllers\ImportExportController::class, 'index'], $property);
$router->get('/properties/{property}/importexport/export/inventory', [\Premisely\Modules\ImportExport\Controllers\ImportExportController::class, 'exportInventory'], $property);
$router->get('/properties/{property}/importexport/export/stock', [\Premisely\Modules\ImportExport\Controllers\ImportExportController::class, 'exportStock'], $property);
$router->get('/properties/{property}/importexport/export/documents-zip', [\Premisely\Modules\ImportExport\Controllers\ImportExportController::class, 'exportDocumentsZip'], $property);
$router->post('/properties/{property}/importexport/import/inventory', [\Premisely\Modules\ImportExport\Controllers\ImportExportController::class, 'importInventory'], $propertyCsrf);
$router->post('/properties/{property}/importexport/import/stock', [\Premisely\Modules\ImportExport\Controllers\ImportExportController::class, 'importStock'], $propertyCsrf);

// V2 — API tokens / Integrations
$router->get('/properties/{property}/api-tokens', [\Premisely\Modules\Api\Controllers\ApiTokenController::class, 'index'], $property);
$router->post('/properties/{property}/api-tokens', [\Premisely\Modules\Api\Controllers\ApiTokenController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/api-tokens/{token}/revoke', [\Premisely\Modules\Api\Controllers\ApiTokenController::class, 'revoke'], $propertyCsrf);
$router->get('/properties/{property}/integrations', [\Premisely\Modules\Integrations\Controllers\IntegrationController::class, 'index'], $property);
$router->post('/properties/{property}/integrations', [\Premisely\Modules\Integrations\Controllers\IntegrationController::class, 'save'], $propertyCsrf);

// Notifications (global)
$router->get('/notifications', [\Premisely\Modules\Notifications\Controllers\NotificationController::class, 'index'], $auth);
$router->post('/notifications/{notification}/read', [\Premisely\Modules\Notifications\Controllers\NotificationController::class, 'markRead'], $authCsrf);

// Files
$router->get('/files/documents/{document}', [FileController::class, 'download'], $auth);
$router->get('/avatars/{user}', [FileController::class, 'avatar'], $auth);
