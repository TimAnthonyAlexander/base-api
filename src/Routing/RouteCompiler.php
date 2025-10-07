<?php

namespace BaseApi\Routing;

use BaseApi\Route;

/**
 * Compiles routes into optimized data structures for fast dispatch.
 * 
 * Separates static routes (O(1) lookup) from dynamic routes (segment-based matching).
 * Precomputes middleware stacks and parameter constraints at compile time.
 */
final class RouteCompiler
{
    /**
     * Compile an array of Route objects into optimized structures.
     * Returns CompiledRoute objects for in-memory use.
     *
     * @param array<string,array<Route>> $routes Routes indexed by method
     * @return array{static: array, dynamic: array, methods: array<string>, allowedMethods: array} Compiled route data
     */
    public function compile(array $routes): array
    {
        $compiled = [
            'static' => [],  // method => [path => CompiledRoute object]
            'dynamic' => [], // method => [CompiledRoute object, ...]
            'methods' => [], // List of all methods that have routes
            'allowedMethods' => [] // path => [methods] for static routes (fast 405/OPTIONS)
        ];

        // First pass: collect all routes
        foreach ($routes as $method => $methodRoutes) {
            $compiled['methods'][] = $method;
            $compiled['static'][$method] = [];
            $compiled['dynamic'][$method] = [];

            foreach ($methodRoutes as $route) {
                $compiledRoute = $this->compileRoute($route);

                if ($compiledRoute->isStatic) {
                    // Static routes go into exact-match map
                    $compiled['static'][$method][$compiledRoute->path] = $compiledRoute;
                } else {
                    // Dynamic routes go into segment-based list
                    $compiled['dynamic'][$method][] = $compiledRoute;
                }
            }

            // Sort dynamic routes by specificity (longest static prefix first)
            usort($compiled['dynamic'][$method], function (CompiledRoute $a, CompiledRoute $b): int {
                $aStaticCount = $this->countStaticSegments($a->segments);
                $bStaticCount = $this->countStaticSegments($b->segments);
                
                if ($aStaticCount !== $bStaticCount) {
                    return $bStaticCount <=> $aStaticCount; // More static segments = higher priority
                }
                
                // If same number of static segments, prefer constrained params over unconstrained
                $aConstrainedCount = count(array_filter($a->paramConstraints, fn($c): bool => $c !== null));
                $bConstrainedCount = count(array_filter($b->paramConstraints, fn($c): bool => $c !== null));
                
                return $bConstrainedCount <=> $aConstrainedCount;
            });
        }

        // Second pass: precompute allowed methods for static routes (for fast 405/OPTIONS)
        foreach ($compiled['static'] as $method => $paths) {
            foreach (array_keys($paths) as $path) {
                if (!isset($compiled['allowedMethods'][$path])) {
                    $compiled['allowedMethods'][$path] = [];
                }

                $compiled['allowedMethods'][$path][] = $method;
            }
        }

        // Add HEAD→GET fallback: if GET exists but HEAD doesn't, register HEAD
        if (isset($compiled['static']['GET'])) {
            if (!isset($compiled['static']['HEAD'])) {
                $compiled['static']['HEAD'] = [];
            }
            
            // Add HEAD to methods list if we're creating HEAD routes
            if (!in_array('HEAD', $compiled['methods'], true)) {
                $compiled['methods'][] = 'HEAD';
            }
            
            foreach ($compiled['static']['GET'] as $path => $route) {
                if (!isset($compiled['static']['HEAD'][$path])) {
                    $compiled['static']['HEAD'][$path] = $route;
                    $compiled['allowedMethods'][$path][] = 'HEAD';
                }
            }
        }

        return $compiled;
    }

    /**
     * Compile a single Route into a CompiledRoute.
     */
    private function compileRoute(Route $route): CompiledRoute
    {
        $path = $route->path();
        $segments = array_filter(explode('/', trim($path, '/')), fn($s): bool => $s !== '');
        $segments = array_values($segments);
         // Reindex
        $paramNames = [];
        $paramConstraints = [];
        $isStatic = true;

        // Analyze each segment
        foreach ($segments as $index => $segment) {
            if (preg_match('/^\{([^:}]+)(?::(.+))?\}$/', $segment, $matches)) {
                // This is a parameter
                $isStatic = false;
                $paramName = $matches[1];
                $constraint = $matches[2] ?? null;

                $paramNames[] = $paramName;

                // Store constraint at segment position
                if ($constraint !== null) {
                    $paramConstraints[$index] = '/^' . $constraint . '$/';
                } else {
                    $paramConstraints[$index] = null; // Unconstrained
                }

                // Keep the segment pattern as-is for matching (e.g., "{id}")
                // This allows CompiledRoute to detect parameter positions
            }
        }

        // Precompute middleware stack
        $middlewares = $route->middlewares();

        return new CompiledRoute(
            method: $route->method(),
            path: $path,
            segments: $segments, // Preserve original segment patterns including {param}
            paramNames: $paramNames,
            paramConstraints: $paramConstraints,
            middlewares: $middlewares,
            controller: $route->controllerClass(),
            isStatic: $isStatic,
            compiledRegex: null // We don't need this for segment-based matching
        );
    }

    /**
     * Count static (non-parameter) segments in a segment list.
     */
    private function countStaticSegments(array $segments): int
    {
        $count = 0;
        foreach ($segments as $segment) {
            if (!str_starts_with((string) $segment, '{')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Export compiled routes to a PHP file for Opcache.
     * Uses atomic write (write to temp file, then rename) for safety.
     * Converts CompiledRoute objects to arrays for better Opcache performance.
     *
     * @param array $compiled Compiled route data
     * @param string $targetPath Path to write the cache file
     */
    public function exportToFile(array $compiled, string $targetPath): void
    {
        // Convert CompiledRoute objects to arrays for better Opcache performance
        $exportData = $this->prepareForExport($compiled);

        // Serialize compiled routes to PHP code
        $code = "<?php\n\n";
        $code .= "// Auto-generated route cache. Do not edit manually.\n";
        $code .= "// Generated at: " . date('Y-m-d H:i:s') . "\n\n";
        $code .= "return " . var_export($exportData, true) . ";\n";

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Atomic write: write to temp file, then rename
        $tempPath = $targetPath . '.' . uniqid('tmp', true);
        file_put_contents($tempPath, $code);
        
        // Atomic rename (overwrites existing file atomically)
        rename($tempPath, $targetPath);
        
        // Invalidate opcache for the file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($targetPath, true);
        }
    }

    /**
     * Prepare compiled data for export by converting objects to arrays.
     */
    private function prepareForExport(array $compiled): array
    {
        $export = [
            'static' => [],
            'dynamic' => [],
            'methods' => $compiled['methods'],
            'allowedMethods' => $compiled['allowedMethods']
        ];

        // Convert static routes
        foreach ($compiled['static'] as $method => $routes) {
            $export['static'][$method] = [];
            foreach ($routes as $path => $compiledRoute) {
                $export['static'][$method][$path] = $this->routeToArray($compiledRoute);
            }
        }

        // Convert dynamic routes
        foreach ($compiled['dynamic'] as $method => $routes) {
            $export['dynamic'][$method] = [];
            foreach ($routes as $compiledRoute) {
                $export['dynamic'][$method][] = $this->routeToArray($compiledRoute);
            }
        }

        return $export;
    }

    /**
     * Convert a CompiledRoute to an array.
     */
    private function routeToArray(CompiledRoute $route): array
    {
        return [
            'method' => $route->method,
            'path' => $route->path,
            'segments' => $route->segments,
            'paramNames' => $route->paramNames,
            'paramConstraints' => $route->paramConstraints,
            'middlewares' => $route->middlewares,
            'controller' => $route->controller,
            'isStatic' => $route->isStatic,
            'compiledRegex' => $route->compiledRegex
        ];
    }

}

