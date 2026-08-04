<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small route table matcher. Each module registers its own routes via
 * routes.php files loaded by the front controller, so a module can be
 * added/removed by adding/removing one require line (spec §19).
 *
 * Route definition: [method, pattern, [controllerClass, method], middleware[]]
 * Pattern supports {param} placeholders, e.g. /transactions/{id}/edit
 */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $action, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $middleware);
    }

    public function post(string $pattern, array $action, array $middleware = []): void
    {
        $this->add('POST', $pattern, $action, $middleware);
    }

    private function add(string $method, string $pattern, array $action, array $middleware): void
    {
        $this->routes[] = compact('method', 'pattern', 'action', 'middleware');
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = $this->match($route['pattern'], $request->path);
            if ($params === null) {
                continue;
            }

            $this->runMiddleware($route['middleware'], function () use ($route, $request, $params) {
                [$controllerClass, $methodName] = $route['action'];
                $controller = new $controllerClass();
                $controller->$methodName($request, ...$params);
            });
            return;
        }

        Response::notFound();
    }

    private function match(string $pattern, string $path): ?array
    {
        $paramNames = [];
        $regex = preg_replace_callback('#\{(\w+)\}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        array_shift($matches);
        return array_combine($paramNames, $matches) ?: [];
    }

    /**
     * Each middleware entry is either a class-string ('AuthMiddleware')
     * or [class-string, ...constructorArgs] for parameterized middleware,
     * e.g. [PermissionMiddleware::class, 'users.manage'].
     */
    private function runMiddleware(array $middleware, callable $final): void
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            function (callable $next, string|array $entry) {
                [$middlewareClass, $args] = is_array($entry)
                    ? [$entry[0], array_slice($entry, 1)]
                    : [$entry, []];
                return fn () => (new $middlewareClass(...$args))->handle($next);
            },
            $final
        );

        $pipeline();
    }
}
