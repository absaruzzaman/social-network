<?php
namespace App\Core;

class Router {
    private array $routes = [];

    private function normalizePath(string $path): string {
        if ($path === '') {
            return '/';
        }

        $normalized = '/' . ltrim($path, '/');
        return rtrim($normalized, '/') ?: '/';
    }

    public function get(string $path, callable $callback): void {
        $normalized = $this->normalizePath($path);
        $this->routes['GET'][$normalized] = $callback;
    }

    public function post(string $path, callable $callback): void {
        $normalized = $this->normalizePath($path);
        $this->routes['POST'][$normalized] = $callback;
    }

    public function dispatch(string $uri, string $method): void {
        $rawPath = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalizePath($rawPath);
        $callback = $this->routes[$method][$path] ?? null;
        
        if (!$callback) {
            http_response_code(404);
            echo "<h1>404 Not Found</h1>";
            echo "<p>Requested: <strong>$method $path</strong></p>";
            echo "<details><summary>Available routes</summary><pre>";
            print_r($this->routes);
            echo "</pre></details>";
            return;
        }

        echo call_user_func($callback);
    }
    public function redirect(string $path)
    {
        header("Location: $path");
        exit;
    }
}