<?php

namespace BaseApi\Console\Commands;

use Throwable;
use Override;
use BaseApi\App;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;

/**
 * Clear compiled route cache.
 */
class RouteClearCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'route:clear';
    }

    #[Override]
    public function description(): string
    {
        return 'Clear compiled route cache';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        try {
            echo ColorHelper::info("🧹 Clearing route cache...") . "\n";

            $cachePath = App::storagePath('cache/routes.php');
            $router = App::router();

            $cleared = $router->clearCache($cachePath);

            if ($cleared) {
                echo ColorHelper::success("✓ Route cache cleared successfully!") . "\n";
                echo "\n";
                echo ColorHelper::comment("Routes will now be compiled on-the-fly from route definitions.") . "\n";
                echo ColorHelper::comment("Run 'route:cache' to recompile for production performance.") . "\n";
                return 0;
            }

            echo ColorHelper::warning("⚠️  No route cache found to clear.") . "\n";
            return 0;
        } catch (Throwable $throwable) {
            echo ColorHelper::error("❌ Error clearing route cache: " . $throwable->getMessage()) . "\n";
            return 1;
        }
    }
}

