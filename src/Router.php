<?php

namespace BaseApi;

use Throwable;
use BaseApi\Routing\CompiledRoute;
use BaseApi\Routing\RouteCompiler;

class Router
{
    private array $routes = [];
    
    private ?array $compiled = null;
    
    private bool $compiledLoaded = false;

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

    public function put(string $path, array $pipeline): void
    {
        $this->addRoute('PUT', $path, $pipeline);
    }

    public function patch(string $path, array $pipeline): void
    {
        $this->addRoute('PATCH', $path, $pipeline);
    }

    public function options(string $path, array $pipeline): void
    {
        $this->addRoute('OPTIONS', $path, $pipeline);
    }

    public function head(string $path, array $pipeline): void
    {
        $this->addRoute('HEAD', $path, $pipeline);
    }

    public function match(string $method, string $path): ?array
    {
        // Normalize path: single pass canonicalization
        $path = $this->normalizePath($path);

        // Try compiled routes first if available
        if ($this->useCompiled()) {
            $result = $this->matchCompiled($method, $path);
            if ($result !== null) {
                return $result;
            }
        }

        // Fall back to traditional route matching
        return $this->matchTraditional($method, $path);
    }

    public function allowedMethodsForPath(string $path): array
    {
        // Normalize path
        $path = $this->normalizePath($path);

        // Try compiled routes first if available
        if ($this->useCompiled()) {
            return $this->allowedMethodsCompiledForPath($path);
        }

        // Fall back to traditional scanning
        return $this->allowedMethodsTraditionalForPath($path);
    }

    /**
     * Compile routes and optionally cache to file.
     *
     * @param string|null $cachePath Path to cache file, or null to compile in-memory only
     * @return bool True if compiled successfully
     */
    public function compile(?string $cachePath = null): bool
    {
        $compiler = new RouteCompiler();
        $this->compiled = $compiler->compile($this->routes);
        $this->compiledLoaded = true;

        if ($cachePath !== null) {
            try {
                $compiler->exportToFile($this->compiled, $cachePath);
                return true;
            } catch (Throwable) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear compiled route cache.
     *
     * @param string $cachePath Path to cache file
     * @return bool True if cleared successfully
     */
    public function clearCache(string $cachePath): bool
    {
        if (file_exists($cachePath)) {
            return unlink($cachePath);
        }

        return true;
    }

    /**
     * Get all registered routes (for inspection/debugging).
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function addRoute(string $method, string $path, array $pipeline): void
    {
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][] = new Route($method, $path, $pipeline);
        
        // Invalidate compiled cache when routes change
        $this->compiled = null;
        $this->compiledLoaded = false;
    }

    /**
     * Normalize path for consistent matching.
     */
    private function normalizePath(string $path): string
    {
        // Ensure leading slash
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        // Remove trailing slash (except for root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Collapse multiple slashes
        $path = preg_replace('#/{2,}#', '/', $path);

        return $path;
    }

    /**
     * Check if we should use compiled routes.
     */
    private function useCompiled(): bool
    {
        if ($this->compiledLoaded) {
            return $this->compiled !== null;
        }

        // Try to load compiled cache
        $cachePath = $this->getCachePath();
        if ($cachePath !== null && file_exists($cachePath)) {
            try {
                $cached = require $cachePath;
                // Convert array-based cache to CompiledRoute objects
                $this->compiled = $this->hydrateCache($cached);
                $this->compiledLoaded = true;
                return true;
            } catch (Throwable) {
                // Cache load failed, fall back to traditional
                $this->compiledLoaded = true;
                return false;
            }
        }

        $this->compiledLoaded = true;
        return false;
    }

    /**
     * Convert array-based cache to CompiledRoute objects.
     */
    private function hydrateCache(array $cached): array
    {
        $hydrated = [
            'static' => [],
            'dynamic' => [],
            'methods' => $cached['methods'],
            'allowedMethods' => $cached['allowedMethods']
        ];

        // Hydrate static routes
        foreach ($cached['static'] as $method => $routes) {
            $hydrated['static'][$method] = [];
            foreach ($routes as $path => $routeData) {
                $hydrated['static'][$method][$path] = $this->arrayToCompiledRoute($routeData);
            }
        }

        // Hydrate dynamic routes
        foreach ($cached['dynamic'] as $method => $routes) {
            $hydrated['dynamic'][$method] = [];
            foreach ($routes as $routeData) {
                $hydrated['dynamic'][$method][] = $this->arrayToCompiledRoute($routeData);
            }
        }

        return $hydrated;
    }

    /**
     * Convert array to CompiledRoute object.
     */
    private function arrayToCompiledRoute(array $data): CompiledRoute
    {
        return new CompiledRoute(
            method: $data['method'],
            path: $data['path'],
            segments: $data['segments'],
            paramNames: $data['paramNames'],
            paramConstraints: $data['paramConstraints'],
            middlewares: $data['middlewares'],
            controller: $data['controller'],
            isStatic: $data['isStatic'],
            compiledRegex: $data['compiledRegex']
        );
    }

    /**
     * Get the cache file path.
     */
    private function getCachePath(): ?string
    {
        // Use storage/cache/routes.php if available
        if (class_exists(App::class)) {
            try {
                return App::storagePath('cache/routes.php');
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Match using compiled routes (fast path).
     */
    private function matchCompiled(string $method, string $path): ?array
    {
        // Method-first filtering: short-circuit if method not present
        if (!in_array($method, $this->compiled['methods'], true)) {
            return null;
        }

        // Try static routes first (O(1) lookup)
        if (isset($this->compiled['static'][$method][$path])) {
            $compiledRoute = $this->compiled['static'][$method][$path];
            return [$this->compiledRouteToRoute($compiledRoute), []];
        }

        // Try dynamic routes (segment-based matching)
        if (isset($this->compiled['dynamic'][$method])) {
            $segments = array_filter(explode('/', trim($path, '/')), fn($s): bool => $s !== '');
            $segments = array_values($segments);

            foreach ($this->compiled['dynamic'][$method] as $compiledRoute) {
                $params = $compiledRoute->matchSegments($segments);
                if ($params !== null) {
                    return [$this->compiledRouteToRoute($compiledRoute), $params];
                }
            }
        }

        return null;
    }

    /**
     * Match using traditional route scanning (fallback).
     */
    private function matchTraditional(string $method, string $path): ?array
    {
        // HEAD→GET fallback: if HEAD requested but not defined, try GET
        if ($method === 'HEAD' && !isset($this->routes['HEAD'])) {
            if (isset($this->routes['GET'])) {
                foreach ($this->routes['GET'] as $route) {
                    $params = $route->match($path);
                    if ($params !== null) {
                        return [$route, $params];
                    }
                }
            }

            return null;
        }

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

    /**
     * Get allowed methods using compiled routes.
     */
    private function allowedMethodsCompiledForPath(string $path): array
    {
        // Fast path: check precomputed allowed methods for static routes
        if (isset($this->compiled['allowedMethods'][$path])) {
            return $this->compiled['allowedMethods'][$path];
        }

        // Slow path: scan dynamic routes (only if not a static route)
        $allowedMethods = [];
        $segments = array_filter(explode('/', trim($path, '/')), fn($s): bool => $s !== '');
        $segments = array_values($segments);

        foreach ($this->compiled['methods'] as $method) {
            // Skip HEAD since it's auto-generated from GET
            if ($method === 'HEAD') {
                continue;
            }

            // Check dynamic routes
            if (isset($this->compiled['dynamic'][$method])) {
                foreach ($this->compiled['dynamic'][$method] as $compiledRoute) {
                    if ($compiledRoute->matchSegments($segments) !== null) {
                        $allowedMethods[] = $method;
                        // If GET is allowed, HEAD is also allowed
                        if ($method === 'GET') {
                            $allowedMethods[] = 'HEAD';
                        }

                        break;
                    }
                }
            }
        }

        return $allowedMethods;
    }

    /**
     * Get allowed methods using traditional scanning.
     */
    private function allowedMethodsTraditionalForPath(string $path): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                if ($route->match($path) !== null) {
                    $allowedMethods[] = $method;
                    // If GET is allowed, HEAD is also allowed (auto-fallback)
                    if ($method === 'GET' && !in_array('HEAD', $allowedMethods, true)) {
                        $allowedMethods[] = 'HEAD';
                    }

                    break;
                }
            }
        }

        return $allowedMethods;
    }

    /**
     * Convert CompiledRoute back to Route for backwards compatibility.
     */
    private function compiledRouteToRoute(CompiledRoute $compiledRoute): Route
    {
        // Reconstruct pipeline from compiled route
        $pipeline = array_merge($compiledRoute->middlewares, [$compiledRoute->controller]);
        
        return new Route($compiledRoute->method, $compiledRoute->path, $pipeline);
    }
}
