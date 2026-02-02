<?php

namespace BaseApi;

use Throwable;
use BaseApi\Routing\RouteCompiler;

/**
 * High-performance router with optimized route matching.
 * 
 * Features:
 * - O(1) static route lookup
 * - O(k) dynamic route matching (k = routes with same segment count)
 * - Zero object allocation in hot path
 * - Precomputed parameter maps and constraints
 * - Fast-path for common constraint types (INT, HEX32)
 * - Automatic HEAD→GET synthesis
 * - Atomic cache writes with durability
 */
class Router
{
    private array $routes = [];

    private ?array $compiled = null;

    private bool $compiledLoaded = false;

    // Constraint type enum (must match RouteCompiler)
    private const int CONSTRAINT_NONE = 0;

    private const int CONSTRAINT_INT = 1;

    private const int CONSTRAINT_HEX32 = 2;

    private const int CONSTRAINT_REGEX = 3;

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
        // Normalize path: fast single-pass canonicalization
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
     * Fast single-pass implementation without regex.
     */
    private function normalizePath(string $path): string
    {
        // Ensure leading slash
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        // Fast path for common case
        if ($path === '/' || !str_contains($path, '//')) {
            // No double slashes, just handle trailing slash
            if ($path !== '/' && $path[strlen($path) - 1] === '/') {
                return substr($path, 0, -1);
            }

            return $path;
        }

        // Collapse multiple slashes in a single pass
        $len = strlen($path);
        $result = '';
        $lastWasSlash = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $path[$i];
            if ($char === '/') {
                if (!$lastWasSlash) {
                    $result .= $char;
                    $lastWasSlash = true;
                }
            } else {
                $result .= $char;
                $lastWasSlash = false;
            }
        }

        // Remove trailing slash (except for root)
        if ($result !== '/' && $result[strlen($result) - 1] === '/') {
            return substr($result, 0, -1);
        }

        return $result;
    }

    /**
     * Check if we should use compiled routes.
     * Loads cache directly as arrays (no object hydration).
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

                // Version check: ensure cache format is compatible
                if (!isset($cached['version']) || $cached['version'] < 2) {
                    // Old format, ignore and rebuild
                    $this->compiledLoaded = true;
                    return false;
                }

                // Use cache directly as arrays (zero hydration)
                $this->compiled = $cached;
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
     * Returns execution plan array [controller, middlewares, params]
     * or Route for backwards compatibility.
     */
    private function matchCompiled(string $method, string $path): ?array
    {
        // Method-first filtering: hash set lookup (O(1))
        if (!isset($this->compiled['methods'][$method])) {
            return null;
        }

        // Try static routes first (O(1) lookup)
        if (isset($this->compiled['static'][$method][$path])) {
            $routeData = $this->compiled['static'][$method][$path];
            return [$this->arrayToRoute($routeData), []];
        }

        // Try dynamic routes using segment count index (O(k) where k = routes with same segment count)
        $segments = array_filter(explode('/', trim($path, '/')), fn($s): bool => $s !== '');
        $segments = array_values($segments);

        $segmentCount = count($segments);

        // Only check routes with matching segment count
        if (isset($this->compiled['dynamicIndex'][$segmentCount][$method])) {
            foreach ($this->compiled['dynamicIndex'][$segmentCount][$method] as $routeData) {
                $params = $this->matchSegments($routeData, $segments);
                if ($params !== null) {
                    // Add HEAD to allowed methods if this is a GET route
                    if ($method === 'HEAD' && ($routeData['allowsHead'] ?? false)) {
                        // This is HEAD request hitting a GET route
                    }

                    return [$this->arrayToRoute($routeData), $params];
                }
            }
        }

        return null;
    }

    /**
     * Fast segment matching with precomputed param maps.
     * Zero-allocation implementation.
     */
    private function matchSegments(array $routeData, array $segments): ?array
    {
        $params = [];
        $segmentCount = count($segments);

        for ($i = 0; $i < $segmentCount; $i++) {
            $pattern = $routeData['segments'][$i];
            $segment = $segments[$i];

            // Check if this segment is a parameter
            if (str_starts_with((string) $pattern, '{')) {
                // Parameter segment - look up precomputed data
                if (!isset($routeData['paramMap'][$i])) {
                    return null;
                }

                $paramName = $routeData['paramMap'][$i];

                // Check constraint using fast-path
                if (isset($routeData['constraintMap'][$i])) {
                    [$constraintType, $constraintPattern] = $routeData['constraintMap'][$i];

                    switch ($constraintType) {
                        case self::CONSTRAINT_NONE:
                            // No constraint, always matches
                            break;

                        case self::CONSTRAINT_INT:
                            // Fast integer check
                            if (!ctype_digit((string) $segment)) {
                                return null;
                            }

                            break;

                        case self::CONSTRAINT_HEX32:
                            // Fast 32-char hex check
                            if (strlen((string) $segment) !== 32 || !ctype_xdigit((string) $segment)) {
                                return null;
                            }

                            break;

                        case self::CONSTRAINT_REGEX:
                            // Fallback to regex
                            if (!preg_match($constraintPattern, (string) $segment)) {
                                return null;
                            }

                            break;
                    }
                }

                $params[$paramName] = $segment;
            } elseif ($pattern !== $segment) {
                // Static segment doesn't match
                return null;
            }
        }

        return $params;
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
     * Get allowed methods using compiled routes with segment count index.
     */
    private function allowedMethodsCompiledForPath(string $path): array
    {
        // Fast path: check precomputed allowed methods for static routes (O(1))
        if (isset($this->compiled['allowedMethods'][$path])) {
            return $this->compiled['allowedMethods'][$path];
        }

        // Dynamic path: use segment count index to scan only relevant routes
        $segments = array_filter(explode('/', trim($path, '/')), fn($s): bool => $s !== '');
        $segments = array_values($segments);

        $segmentCount = count($segments);

        $methodSet = [];

        // Only check routes with matching segment count
        if (isset($this->compiled['dynamicIndex'][$segmentCount])) {
            foreach ($this->compiled['dynamicIndex'][$segmentCount] as $method => $routes) {
                // Skip HEAD since it's synthesized from GET
                if ($method === 'HEAD') {
                    continue;
                }

                foreach ($routes as $routeData) {
                    if ($this->matchSegments($routeData, $segments) !== null) {
                        $methodSet[$method] = 1;
                        // If GET is allowed, HEAD is also allowed
                        if ($method === 'GET') {
                            $methodSet['HEAD'] = 1;
                        }

                        break;
                    }
                }
            }
        }

        // Convert to normalized array
        return $this->normalizeMethodList(array_keys($methodSet));
    }

    /**
     * Normalize method list: dedupe and sort in standard order.
     */
    private function normalizeMethodList(array $methods): array
    {
        $methodOrder = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $methodSet = array_flip($methods);
        $normalized = [];

        foreach ($methodOrder as $method) {
            if (isset($methodSet[$method])) {
                $normalized[] = $method;
            }
        }

        // Add any non-standard methods at the end
        foreach ($methods as $method) {
            if (!in_array($method, $methodOrder, true) && !in_array($method, $normalized, true)) {
                $normalized[] = $method;
            }
        }

        return $normalized;
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
     * Convert route array back to Route object for backwards compatibility.
     */
    private function arrayToRoute(array $routeData): Route
    {
        // Reconstruct pipeline from route data
        // Controller can be a string or array [class, method]
        $pipeline = array_merge($routeData['middlewares'], [$routeData['controller']]);

        return new Route($routeData['method'], $routeData['path'], $pipeline);
    }
}
