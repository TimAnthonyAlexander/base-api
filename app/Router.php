<?php

namespace BaseApi;

class Router
{
    private array $routes = [];

    public function get(string $path, array $pipeline): void
    {
        $this->addRoute('GET', $path, $pipeline);
    }

    public function post(string $path, array $pipeline): void
    {
        $this->addRoute('POST', $path, $pipeline);
    }

    public function delete(string $path, array $pipeline): void
    {
        $this->addRoute('DELETE', $path, $pipeline);
    }

    public function match(string $method, string $path): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $route) {
            $params = $route->match($path);
            if ($params !== null) {
                return [$route, $params];
            }
        }

        return null;
    }

    public function allowedMethodsForPath(string $path): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                if ($route->match($path) !== null) {
                    $allowedMethods[] = $method;
                    break;
                }
            }
        }

        return $allowedMethods;
    }

    private function addRoute(string $method, string $path, array $pipeline): void
    {
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][] = new Route($method, $path, $pipeline);
    }
}
