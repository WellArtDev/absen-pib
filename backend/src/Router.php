<?php
declare(strict_types=1);

namespace App;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, string $middleware = 'none'): void
    {
        $this->routes[] = ['GET', $path, $handler, $middleware];
    }

    public function post(string $path, callable $handler, string $middleware = 'none'): void
    {
        $this->routes[] = ['POST', $path, $handler, $middleware];
    }

    public function put(string $path, callable $handler, string $middleware = 'none'): void
    {
        $this->routes[] = ['PUT', $path, $handler, $middleware];
    }

    public function delete(string $path, callable $handler, string $middleware = 'none'): void
    {
        $this->routes[] = ['DELETE', $path, $handler, $middleware];
    }

    public function dispatch(string $method, string $uri, array $body, array $query): void
    {
        foreach ($this->routes as [$routeMethod, $routePath, $handler, $middleware]) {
            $params = $this->match($routePath, $uri);
            if ($params !== false && $method === $routeMethod) {
                // Apply middleware
                match ($middleware) {
                    'auth' => Middleware::auth(),
                    'admin' => Middleware::admin(),
                    'owner' => Middleware::owner(),
                    'superadmin' => Middleware::superadmin(),
                    default => null,
                };
                $handler($params, $body, $query);
                return;
            }
        }

        Response::error('Route tidak ditemukan: ' . $method . ' ' . $uri, 404);
    }

    private function match(string $routePath, string $uri): array|false
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($routeParts) !== count($uriParts)) return false;

        $params = [];
        for ($i = 0; $i < count($routeParts); $i++) {
            $r = $routeParts[$i];
            $u = $uriParts[$i];

            // Dynamic segment :param
            if (str_starts_with($r, ':')) {
                $params[substr($r, 1)] = $u;
                continue;
            }

            if ($r !== $u) return false;
        }

        return $params;
    }
}

// Only for autocomplete, not used at runtime. Routes defined in routes.php.
