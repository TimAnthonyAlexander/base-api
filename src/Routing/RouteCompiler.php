<?php

namespace BaseApi\Routing;

use RuntimeException;
use BaseApi\Route;

/**
 * Compiles routes into optimized data structures for fast dispatch.
 * 
 * Separates static routes (O(1) lookup) from dynamic routes (segment-based matching).
 * Precomputes middleware stacks and parameter constraints at compile time.
 * 
 * Optimizations:
 * - No object hydration: cache is pure arrays
 * - Dynamic routes indexed by segment count (O(k) not O(R))
 * - Methods as hash set for isset() checks
 * - Precomputed param maps for zero-allocation matching
 * - Fast-path for common constraints (INT, HEX32)
 * - HEAD synthesis for all GET routes
 * - Deduped and normalized allowed methods
 */
final class RouteCompiler
{
    // Standard method order for normalized output
    private const array METHOD_ORDER = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    // Constraint type enum for fast-path matching
    private const int CONSTRAINT_NONE = 0;

    private const int CONSTRAINT_INT = 1;

    private const int CONSTRAINT_HEX32 = 2;

    private const int CONSTRAINT_REGEX = 3;

    /**
     * Compile an array of Route objects into optimized structures.
     * Returns pure array structures (no objects).
     *
     * @param array<string,array<Route>> $routes Routes indexed by method
     * @return array Compiled route data as pure arrays
     */
    public function compile(array $routes): array
    {
        $compiled = [
            'static' => [],  // method => [path => route_array]
            'dynamicIndex' => [], // segment_count => method => [route_array, ...]
            'methods' => [], // Hash set: [method => 1]
            'allowedMethods' => [], // path => [methods] (deduped, normalized order)
            'version' => 2 // Cache version for compatibility checks
        ];

        $allDynamicRoutes = []; // Collect for indexing by segment count

        // First pass: collect all routes
        foreach ($routes as $method => $methodRoutes) {
            $compiled['methods'][$method] = 1; // Hash set

            if (!isset($compiled['static'][$method])) {
                $compiled['static'][$method] = [];
            }

            foreach ($methodRoutes as $route) {
                $compiledRoute = $this->compileRouteToArray($route);

                if ($compiledRoute['isStatic']) {
                    // Static routes go into exact-match map
                    $compiled['static'][$method][$compiledRoute['path']] = $compiledRoute;

                    // Track allowed methods for static routes
                    if (!isset($compiled['allowedMethods'][$compiledRoute['path']])) {
                        $compiled['allowedMethods'][$compiledRoute['path']] = [];
                    }

                    $compiled['allowedMethods'][$compiledRoute['path']][$method] = 1;
                } else {
                    // Collect dynamic routes for indexing
                    $allDynamicRoutes[] = [
                        'method' => $method,
                        'route' => $compiledRoute
                    ];
                }
            }
        }

        // Sort dynamic routes by deterministic specificity
        usort($allDynamicRoutes, fn(array $a, array $b): int => $this->compareSpecificity($a['route'], $b['route']));

        // Index dynamic routes by segment count
        foreach ($allDynamicRoutes as $item) {
            $method = $item['method'];
            $route = $item['route'];
            $segmentCount = $route['segmentCount'];

            if (!isset($compiled['dynamicIndex'][$segmentCount])) {
                $compiled['dynamicIndex'][$segmentCount] = [];
            }

            if (!isset($compiled['dynamicIndex'][$segmentCount][$method])) {
                $compiled['dynamicIndex'][$segmentCount][$method] = [];
            }

            $compiled['dynamicIndex'][$segmentCount][$method][] = $route;
        }

        // Add HEAD→GET fallback for static routes
        if (isset($compiled['static']['GET'])) {
            if (!isset($compiled['static']['HEAD'])) {
                $compiled['static']['HEAD'] = [];
            }

            $compiled['methods']['HEAD'] = 1;

            foreach ($compiled['static']['GET'] as $path => $route) {
                if (!isset($compiled['static']['HEAD'][$path])) {
                    $compiled['static']['HEAD'][$path] = $route;
                    $compiled['allowedMethods'][$path]['HEAD'] = 1;
                }
            }
        }

        // Add HEAD→GET fallback for dynamic routes
        foreach ($compiled['dynamicIndex'] as $segmentCount => $methods) {
            if (isset($methods['GET'])) {
                if (!isset($compiled['dynamicIndex'][$segmentCount]['HEAD'])) {
                    $compiled['dynamicIndex'][$segmentCount]['HEAD'] = [];
                }

                // Mark GET routes as HEAD-allowed
                foreach ($methods['GET'] as $route) {
                    $headRoute = $route;
                    $headRoute['allowsHead'] = true;
                    $compiled['dynamicIndex'][$segmentCount]['HEAD'][] = $headRoute;
                }
            }
        }

        // Normalize allowed methods: dedupe and sort
        foreach ($compiled['allowedMethods'] as $path => $methodSet) {
            $compiled['allowedMethods'][$path] = $this->normalizeMethodList(array_keys($methodSet));
        }

        return $compiled;
    }

    /**
     * Compile a single Route into an array (no objects).
     */
    private function compileRouteToArray(Route $route): array
    {
        $path = $route->path();
        $segments = array_filter(explode('/', trim($path, '/')), fn($s): bool => $s !== '');
        $segments = array_values($segments);

        $paramNames = [];
        $paramMap = []; // Position => name
        $constraintMap = []; // Position => [type, pattern]
        $isStatic = true;
        $staticCount = 0;

        // Analyze each segment
        foreach ($segments as $index => $segment) {
            if (preg_match('/^\{([^:}]+)(?::(.+))?\}$/', $segment, $matches)) {
                // This is a parameter
                $isStatic = false;
                $paramName = $matches[1];
                $constraint = $matches[2] ?? null;

                $paramNames[] = $paramName;
                $paramMap[$index] = $paramName;

                // Detect fast-path constraints
                if ($constraint === null) {
                    $constraintMap[$index] = [self::CONSTRAINT_NONE, null];
                } elseif ($constraint === '\d+' || $constraint === '[0-9]+') {
                    $constraintMap[$index] = [self::CONSTRAINT_INT, null];
                } elseif ($constraint === '[0-9a-f]{32}' || $constraint === '[0-9a-fA-F]{32}') {
                    $constraintMap[$index] = [self::CONSTRAINT_HEX32, null];
                } else {
                    $constraintMap[$index] = [self::CONSTRAINT_REGEX, '/^' . $constraint . '$/'];
                }
            } else {
                $staticCount++;
            }
        }

        // Store controller class and optional custom method
        $controllerData = $route->controllerClass();
        $customMethod = $route->controllerMethod();

        // If custom method is provided, store as array [class, method]
        if ($customMethod !== null) {
            $controllerData = [$route->controllerClass(), $customMethod];
        }

        return [
            'method' => $route->method(),
            'path' => $path,
            'segments' => $segments,
            'segmentCount' => count($segments),
            'staticCount' => $staticCount,
            'paramNames' => $paramNames,
            'paramMap' => $paramMap, // Position => name
            'constraintMap' => $constraintMap, // Position => [type, pattern]
            'middlewares' => $route->middlewares(),
            'controller' => $controllerData,
            'isStatic' => $isStatic,
            'allowsHead' => false // Will be set for GET routes
        ];
    }

    /**
     * Compare two routes for deterministic specificity ordering.
     * More specific routes come first.
     */
    private function compareSpecificity(array $a, array $b): int
    {
        // 1. More static segments = higher priority
        if ($a['staticCount'] !== $b['staticCount']) {
            return $b['staticCount'] <=> $a['staticCount'];
        }

        // 2. More constrained params = higher priority
        $aConstrained = count(array_filter($a['constraintMap'], fn($c): bool => $c[0] !== self::CONSTRAINT_NONE));
        $bConstrained = count(array_filter($b['constraintMap'], fn($c): bool => $c[0] !== self::CONSTRAINT_NONE));

        if ($aConstrained !== $bConstrained) {
            return $bConstrained <=> $aConstrained;
        }

        // 3. More total segments = higher priority
        if ($a['segmentCount'] !== $b['segmentCount']) {
            return $b['segmentCount'] <=> $a['segmentCount'];
        }

        // 4. Lexicographic path for stable order
        return strcmp((string) $a['path'], (string) $b['path']);
    }

    /**
     * Normalize method list: dedupe and sort in standard order.
     */
    private function normalizeMethodList(array $methods): array
    {
        $methodSet = array_flip($methods);
        $normalized = [];

        foreach (self::METHOD_ORDER as $method) {
            if (isset($methodSet[$method])) {
                $normalized[] = $method;
            }
        }

        // Add any non-standard methods at the end
        foreach ($methods as $method) {
            if (!in_array($method, self::METHOD_ORDER, true) && !in_array($method, $normalized, true)) {
                $normalized[] = $method;
            }
        }

        return $normalized;
    }

    /**
     * Export compiled routes to a PHP file for Opcache.
     * Uses atomic write with flock, fflush, and fsync for durability.
     *
     * @param array $compiled Compiled route data (already as arrays)
     * @param string $targetPath Path to write the cache file
     */
    public function exportToFile(array $compiled, string $targetPath): void
    {
        // Serialize compiled routes to PHP code
        $code = "<?php\n\n";
        $code .= "// Auto-generated route cache. Do not edit manually.\n";
        $code .= "// Generated at: " . date('Y-m-d H:i:s') . "\n";
        $code .= "// Version: " . ($compiled['version'] ?? 1) . "\n\n";
        $code .= "return " . var_export($compiled, true) . ";\n";

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Atomic write with durability: write to temp file with flock
        $tempPath = $targetPath . '.' . uniqid('tmp', true);
        $fp = fopen($tempPath, 'wb');

        if ($fp === false) {
            throw new RuntimeException('Failed to open temp file for writing: ' . $tempPath);
        }

        // Lock file for exclusive write
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException('Failed to acquire lock on temp file: ' . $tempPath);
        }

        // Write content
        fwrite($fp, $code);

        // Flush PHP buffers
        fflush($fp);

        // Sync to disk (fsync equivalent in PHP)
        if (function_exists('fsync')) {
            fsync($fp);
        }

        // Release lock and close
        flock($fp, LOCK_UN);
        fclose($fp);

        // Atomic rename (overwrites existing file atomically on POSIX)
        rename($tempPath, $targetPath);

        // Invalidate opcache for the file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($targetPath, true);
        }
    }
}

