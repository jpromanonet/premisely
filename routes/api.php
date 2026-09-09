<?php

declare(strict_types=1);

use Premisely\Core\Middleware\ApiAuthMiddleware;
use Premisely\Core\Middleware\AuthMiddleware;
use Premisely\Core\Middleware\CsrfMiddleware;
use Premisely\Core\Middleware\HaPropertyMiddleware;
use Premisely\Core\Middleware\PropertyAccessMiddleware;
use Premisely\Modules\Api\Controllers\ApiV1Controller;
use Premisely\Modules\Integrations\Controllers\IntegrationController;
use Premisely\Modules\Stock\Controllers\StockController;
use Premisely\Modules\Tasks\Controllers\TaskController;

/** @var \Premisely\Core\Routing\Router $router */

$sessionApi = [AuthMiddleware::class, CsrfMiddleware::class, PropertyAccessMiddleware::class];
$tokenApi = [ApiAuthMiddleware::class, PropertyAccessMiddleware::class];
$tokenApiGlobal = [ApiAuthMiddleware::class];
$haApi = [HaPropertyMiddleware::class];

// Legacy session+CSRF endpoints (UI/XHR)
$router->post('/api/properties/{property}/stock/{item}/adjust', [StockController::class, 'adjust'], $sessionApi);
$router->post('/api/properties/{property}/tasks/{task}/status', [TaskController::class, 'updateStatus'], $sessionApi);

// API v1 — Bearer token or session (no CSRF for Bearer)
$router->get('/api/v1/properties', [ApiV1Controller::class, 'properties'], $tokenApiGlobal);
$router->get('/api/v1/properties/{property}/spaces', [ApiV1Controller::class, 'spaces'], $tokenApi);
$router->get('/api/v1/properties/{property}/inventory', [ApiV1Controller::class, 'inventory'], $tokenApi);
$router->get('/api/v1/properties/{property}/stock', [ApiV1Controller::class, 'stock'], $tokenApi);
$router->get('/api/v1/properties/{property}/tasks', [ApiV1Controller::class, 'tasks'], $tokenApi);
$router->post('/api/v1/properties/{property}/stock/{item}/adjust', [StockController::class, 'adjust'], $tokenApi);
$router->post('/api/v1/properties/{property}/tasks/{task}/status', [TaskController::class, 'updateStatus'], $tokenApi);
$router->post('/api/v1/properties/{property}/integrations/ha', [IntegrationController::class, 'homeAssistant'], $haApi);
