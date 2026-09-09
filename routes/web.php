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
$router->get('/properties/{property}/dashboard', [DashboardController::class, 'property'], $property);

// Members
$router->get('/properties/{property}/members', [MemberController::class, 'index'], $property);
$router->post('/properties/{property}/members', [MemberController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/members/leave', [MemberController::class, 'leave'], $propertyCsrf);
$router->post('/properties/{property}/members/{member}', [MemberController::class, 'update'], $propertyCsrf);

// Spaces
$router->get('/properties/{property}/spaces', [SpaceController::class, 'index'], $property);
$router->post('/properties/{property}/spaces', [SpaceController::class, 'store'], $propertyCsrf);
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
$router->post('/properties/{property}/stock', [StockController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/stock/{item}', [StockController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/stock/{item}/adjust', [StockController::class, 'adjust'], $propertyCsrf);
$router->post('/properties/{property}/stock/{item}/archive', [StockController::class, 'archive'], $propertyCsrf);

// Shopping
$router->get('/properties/{property}/shopping', [ShoppingController::class, 'index'], $property);
$router->post('/properties/{property}/shopping', [ShoppingController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/shopping/{item}/complete', [ShoppingController::class, 'complete'], $propertyCsrf);
$router->post('/properties/{property}/shopping/{item}/delete', [ShoppingController::class, 'delete'], $propertyCsrf);

// Tasks
$router->get('/properties/{property}/tasks', [TaskController::class, 'index'], $property);
$router->post('/properties/{property}/tasks', [TaskController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/tasks/{task}/status', [TaskController::class, 'updateStatus'], $propertyCsrf);
$router->post('/properties/{property}/tasks/{task}/archive', [TaskController::class, 'archive'], $propertyCsrf);

// Routines
$router->get('/properties/{property}/routines', [RoutineController::class, 'index'], $property);
$router->post('/properties/{property}/routines', [RoutineController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/routines/{routine}/execute', [RoutineController::class, 'execute'], $propertyCsrf);

// Cleaning
$router->get('/properties/{property}/cleaning', [CleaningController::class, 'index'], $property);
$router->post('/properties/{property}/cleaning', [CleaningController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/cleaning/{routine}/execute', [CleaningController::class, 'execute'], $propertyCsrf);

// Maintenance
$router->get('/properties/{property}/maintenance', [MaintenanceController::class, 'index'], $property);
$router->post('/properties/{property}/maintenance/plans', [MaintenanceController::class, 'storePlan'], $propertyCsrf);
$router->post('/properties/{property}/maintenance/records', [MaintenanceController::class, 'storeRecord'], $propertyCsrf);

// Repairs
$router->get('/properties/{property}/repairs', [RepairController::class, 'index'], $property);
$router->post('/properties/{property}/repairs', [RepairController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/repairs/{repair}', [RepairController::class, 'update'], $propertyCsrf);
$router->post('/properties/{property}/repairs/{repair}/close', [RepairController::class, 'close'], $propertyCsrf);

// Services
$router->get('/properties/{property}/services', [ServiceController::class, 'index'], $property);
$router->post('/properties/{property}/services', [ServiceController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/services/{service}/bills', [ServiceController::class, 'addBill'], $propertyCsrf);

// Expenses
$router->get('/properties/{property}/expenses', [ExpenseController::class, 'index'], $property);
$router->post('/properties/{property}/expenses', [ExpenseController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/expenses/{expense}/archive', [ExpenseController::class, 'archive'], $propertyCsrf);

// Documents
$router->get('/properties/{property}/documents', [DocumentController::class, 'index'], $property);
$router->post('/properties/{property}/documents', [DocumentController::class, 'store'], $propertyCsrf);
$router->post('/properties/{property}/documents/{document}/archive', [DocumentController::class, 'archive'], $propertyCsrf);

// Search
$router->get('/properties/{property}/search', [SearchController::class, 'index'], $property);

// Files
$router->get('/files/documents/{document}', [FileController::class, 'download'], $auth);
