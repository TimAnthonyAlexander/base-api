<?php

namespace BaseApi\Console\Commands;

use Throwable;
use Override;
use BaseApi\App;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;

/**
 * List all registered routes.
 */
class RouteListCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'route:list';
    }

    #[Override]
    public function description(): string
    {
        return 'List all registered routes';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        try {
            $router = App::router();
            $routes = $router->getRoutes();

            if ($routes === []) {
                echo ColorHelper::warning("⚠️  No routes registered.") . "\n";
                return 0;
            }

            echo ColorHelper::info("📋 Registered Routes") . "\n";
            echo str_repeat('─', 120) . "\n";
            echo sprintf(
                "%-8s %-40s %-50s %s\n",
                ColorHelper::colorize('METHOD', ColorHelper::BRIGHT_WHITE),
                ColorHelper::colorize('PATH', ColorHelper::BRIGHT_WHITE),
                ColorHelper::colorize('CONTROLLER', ColorHelper::BRIGHT_WHITE),
                ColorHelper::colorize('MIDDLEWARE', ColorHelper::BRIGHT_WHITE)
            );
            echo str_repeat('─', 120) . "\n";

            $totalRoutes = 0;
            $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

            foreach ($methods as $method) {
                if (!isset($routes[$method])) {
                    continue;
                }

                foreach ($routes[$method] as $route) {
                    $totalRoutes++;
                    
                    $middlewares = $route->middlewares();
                    $middlewareCount = count($middlewares);
                    $middlewareStr = $middlewareCount > 0 ? $middlewareCount . ' middleware(s)' : '-';

                    // Color-code methods
                    $methodColor = match($method) {
                        'GET' => "\033[32m", // Green
                        'POST' => "\033[34m", // Blue
                        'PUT', 'PATCH' => "\033[33m", // Yellow
                        'DELETE' => "\033[31m", // Red
                        default => "\033[37m", // White
                    };

                    printf(
                        "%s%-8s\033[0m %-40s %-50s %s\n",
                        $methodColor,
                        $method,
                        $this->truncate($route->path(), 40),
                        $this->truncate($this->formatController($route->controllerClass()), 50),
                        ColorHelper::comment($middlewareStr)
                    );
                }
            }

            echo str_repeat('─', 120) . "\n";
            echo ColorHelper::success(sprintf("Total: %d route(s)", $totalRoutes)) . "\n";

            // Check if cache exists
            $cachePath = App::storagePath('cache/routes.php');
            if (file_exists($cachePath)) {
                echo "\n";
                echo ColorHelper::info("🚀 Compiled cache: ACTIVE") . " ";
                echo ColorHelper::comment("(using optimized dispatch)") . "\n";
            } else {
                echo "\n";
                echo ColorHelper::comment("💡 Tip: Run 'route:cache' to compile routes for production performance.") . "\n";
            }

            return 0;
        } catch (Throwable $throwable) {
            echo ColorHelper::error("❌ Error listing routes: " . $throwable->getMessage()) . "\n";
            return 1;
        }
    }

    private function formatController(string $controller): string
    {
        // Strip namespace prefix for readability
        $parts = explode('\\', $controller);
        return end($parts);
    }

    private function truncate(string $str, int $length): string
    {
        if (mb_strlen($str) <= $length) {
            return $str;
        }

        return mb_substr($str, 0, $length - 3) . '...';
    }
}

