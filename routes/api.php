<?php

declare(strict_types=1);

use Premisely\Core\Middleware\AuthMiddleware;
use Premisely\Core\Middleware\CsrfMiddleware;
use Premisely\Core\Middleware\PropertyAccessMiddleware;
use Premisely\Modules\Stock\Controllers\StockController;
use Premisely\Modules\Tasks\Controllers\TaskController;

/** @var \Premisely\Core\Routing\Router $router */

$api = [AuthMiddleware::class, CsrfMiddleware::class, PropertyAccessMiddleware::class];

$router->post('/api/properties/{property}/stock/{item}/adjust', [StockController::class, 'adjust'], $api);
$router->post('/api/properties/{property}/tasks/{task}/status', [TaskController::class, 'updateStatus'], $api);
