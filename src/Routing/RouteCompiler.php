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
     *
     * @param array<string,array<Route>> $routes Routes indexed by method
     * @return array{static: array, dynamic: array, methods: array<string>} Compiled route data
     */
    public function compile(array $routes): array
    {
        $compiled = [
            'static' => [],  // method => [path => CompiledRoute]
            'dynamic' => [], // method => [CompiledRoute, ...]
            'methods' => []  // List of all methods that have routes
        ];

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
                    // Sorted by specificity (most specific first)
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
     *
     * @param array $compiled Compiled route data
     * @param string $targetPath Path to write the cache file
     */
    public function exportToFile(array $compiled, string $targetPath): void
    {
        // Serialize compiled routes to PHP code
        $code = "<?php\n\n";
        $code .= "// Auto-generated route cache. Do not edit manually.\n";
        $code .= "// Generated at: " . date('Y-m-d H:i:s') . "\n\n";
        $code .= "return " . $this->varExport($compiled) . ";\n";

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($targetPath, $code);
        
        // Try to set opcache timestamp for the file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($targetPath, true);
        }
    }

    /**
     * Custom var_export that produces cleaner, more Opcache-friendly code.
     */
    private function varExport(mixed $value, int $indent = 0): string
    {
        if (is_array($value)) {
            if ($value === []) {
                return '[]';
            }

            $isAssoc = array_keys($value) !== range(0, count($value) - 1);
            $items = [];
            $indentStr = str_repeat('    ', $indent + 1);
            
            foreach ($value as $key => $val) {
                if ($isAssoc) {
                    $exportedKey = is_string($key) ? "'" . addslashes($key) . "'" : $key;
                    $items[] = $indentStr . $exportedKey . ' => ' . $this->varExport($val, $indent + 1);
                } else {
                    $items[] = $indentStr . $this->varExport($val, $indent + 1);
                }
            }

            $closeIndent = str_repeat('    ', $indent);
            return "[\n" . implode(",\n", $items) . ",\n" . $closeIndent . "]";
        }

        if ($value instanceof CompiledRoute) {
            // Export CompiledRoute as constructor call
            return sprintf(
                "new \\BaseApi\\Routing\\CompiledRoute(\n" .
                "    method: %s,\n" .
                "    path: %s,\n" .
                "    segments: %s,\n" .
                "    paramNames: %s,\n" .
                "    paramConstraints: %s,\n" .
                "    middlewares: %s,\n" .
                "    controller: %s,\n" .
                "    isStatic: %s,\n" .
                "    compiledRegex: %s\n" .
                ")",
                $this->varExport($value->method),
                $this->varExport($value->path),
                $this->varExport($value->segments),
                $this->varExport($value->paramNames),
                $this->varExport($value->paramConstraints),
                $this->varExport($value->middlewares),
                $this->varExport($value->controller),
                $value->isStatic ? 'true' : 'false',
                $value->compiledRegex === null ? 'null' : $this->varExport($value->compiledRegex)
            );
        }

        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return var_export($value, true);
    }
}

