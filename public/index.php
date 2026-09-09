<?php

declare(strict_types=1);

use Premisely\Core\Http\Request;

$boot = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var \Premisely\Core\Routing\Router $router */
$router = $boot['router'];
/** @var Request $request */
$request = $boot['request'];

$router->dispatch($request);
