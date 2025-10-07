<?php

namespace BaseApi\Console\Commands;

use Throwable;
use Override;
use BaseApi\App;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;

/**
 * Cache compiled routes for optimal performance.
 */
class RouteCacheCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'route:cache';
    }

    #[Override]
    public function description(): string
    {
        return 'Compile and cache routes for optimal dispatch performance';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        try {
            echo ColorHelper::info("🚀 Compiling routes...") . "\n";

            // Load application routes
            $this->loadRoutes();

            $router = App::router();
            $cachePath = App::storagePath('cache/routes.php');

            // Ensure cache directory exists
            $cacheDir = dirname($cachePath);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            // Compile routes
            $success = $router->compile($cachePath);

            if (!$success) {
                echo ColorHelper::error("❌ Failed to compile routes.") . "\n";
                return 1;
            }

            // Get route stats
            $routes = $router->getRoutes();
            $totalRoutes = 0;
            $staticRoutes = 0;
            
            foreach ($routes as $methodRoutes) {
                foreach ($methodRoutes as $route) {
                    $totalRoutes++;
                    // Check if route has no parameters
                    if (!str_contains((string) $route->path(), '{')) {
                        $staticRoutes++;
                    }
                }
            }

            $dynamicRoutes = $totalRoutes - $staticRoutes;

            echo ColorHelper::success("✓ Routes compiled successfully!") . "\n";
            echo "\n";
            echo "  📊 Statistics:\n";
            echo "     Total routes:    " . ColorHelper::colorize((string) $totalRoutes, ColorHelper::BRIGHT_CYAN) . "\n";
            echo "     Static routes:   " . ColorHelper::colorize((string) $staticRoutes, ColorHelper::BRIGHT_CYAN) . " (O(1) lookup)\n";
            echo "     Dynamic routes:  " . ColorHelper::colorize((string) $dynamicRoutes, ColorHelper::BRIGHT_CYAN) . " (segment-based)\n";
            echo "\n";
            echo "  📁 Cache file: " . ColorHelper::comment($cachePath) . "\n";
            echo "  💾 File size:  " . ColorHelper::comment($this->formatBytes(filesize($cachePath))) . "\n";
            echo "\n";
            echo ColorHelper::comment("Tip: Routes will be loaded from cache on next request for maximum performance.") . "\n";

            return 0;
        } catch (Throwable $throwable) {
            echo ColorHelper::error("❌ Error compiling routes: " . $throwable->getMessage()) . "\n";
            if (isset($args[0]) && $args[0] === '--verbose') {
                echo ColorHelper::comment($throwable->getTraceAsString()) . "\n";
            }

            return 1;
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        
        if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return round($bytes / 1048576, 2) . ' MB';
    }

    /**
     * Load application routes from the routes file.
     */
    private function loadRoutes(): void
    {
        $routesFile = App::basePath('routes/api.php');
        
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }
}

