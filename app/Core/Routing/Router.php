<?php

declare(strict_types=1);

namespace Premisely\Core\Routing;

use Premisely\Core\Http\Request;
use Premisely\Core\Http\Response;
use Premisely\Core\Logging\Logger;
use Throwable;

final class Router
{
    /** @var list<array{method:string,pattern:string,regex:string,handler:callable|array{0:class-string,1:string},middleware:list<callable|class-string>}> */
    private array $routes = [];

    /** @param callable|array{0:class-string,1:string} $handler @param list<callable|class-string> $middleware */
    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param callable|array{0:class-string,1:string} $handler @param list<callable|class-string> $middleware */
    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param callable|array{0:class-string,1:string} $handler @param list<callable|class-string> $middleware */
    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    /** @param callable|array{0:class-string,1:string} $handler @param list<callable|class-string> $middleware */
    public function patch(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    /** @param callable|array{0:class-string,1:string} $handler @param list<callable|class-string> $middleware */
    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /** @param callable|array{0:class-string,1:string} $handler @param list<callable|class-string> $middleware */
    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $pattern = $this->normalize($path);
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $this->normalize($request->path());

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            try {
                $handler = $this->resolveHandler($route['handler']);
                $pipeline = array_reduce(
                    array_reverse($route['middleware']),
                    static function (callable $next, callable|string $mw): callable {
                        return static function (Request $req, array $params) use ($mw, $next) {
                            if (is_string($mw)) {
                                $instance = new $mw();
                                return $instance->handle($req, $params, $next);
                            }
                            return $mw($req, $params, $next);
                        };
                    },
                    static function (Request $req, array $params) use ($handler) {
                        return $handler($req, $params);
                    }
                );

                $pipeline($request, $params);
                return;
            } catch (Throwable $e) {
                Logger::error('router.dispatch', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                if (config('app.debug')) {
                    Response::html('<pre>' . e($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>', 500)->send();
                }
                Response::html(view('errors/500', ['message' => 'Error interno']), 500)->send();
            }
        }

        Response::html(view('errors/404'), 404)->send();
    }

    /** @param callable|array{0:class-string,1:string} $handler */
    private function resolveHandler(callable|array $handler): callable
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            return static function (Request $request, array $params) use ($instance, $method) {
                return $instance->{$method}($request, $params);
            };
        }
        return $handler;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
