<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;
use BaseApi\Cache\Cache;
use BaseApi\App;

/**
 * Clean up expired cache entries command.
 */
class CacheCleanupCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'cache:cleanup';
    }

    #[Override]
    public function description(): string
    {
        return 'Clean up expired cache entries';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $driver = $args[0] ?? null;
        $config = App::config();

        try {
            if ($driver) {
                // Cleanup specific driver
                $removed = $this->cleanupDriver($driver);
                echo ColorHelper::success(sprintf('Cleaned up %d expired entries from %s cache.', $removed, $driver)) . "\n";
            } else {
                // Cleanup all drivers that support it
                $stores = $config->get('cache.stores', []);
                $totalRemoved = 0;
                $driversProcessed = 0;

                echo ColorHelper::info("🧹 Cleaning up expired cache entries...") . "\n";

                foreach (array_keys($stores) as $storeName) {
                    try {
                        $removed = $this->cleanupDriver($storeName);
                        if ($removed > 0) {
                            echo ColorHelper::success(sprintf('  ✓ %s: removed %d expired entries', $storeName, $removed)) . "\n";
                            $totalRemoved += $removed;
                        } else {
                            echo ColorHelper::comment(sprintf('  ✓ %s: no expired entries found', $storeName)) . "\n";
                        }

                        $driversProcessed++;
                    } catch (Exception $e) {
                        echo ColorHelper::error(sprintf('  ✗ %s: ', $storeName) . $e->getMessage()) . "\n";
                    }
                }

                if ($totalRemoved > 0) {
                    echo ColorHelper::success(sprintf('Cleanup completed. Removed %d expired entries from %d drivers.', $totalRemoved, $driversProcessed)) . "\n";
                } else {
                    echo ColorHelper::info(sprintf(' Cleanup completed. No expired entries found in %d drivers.', $driversProcessed)) . "\n";
                }
            }

            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error during cache cleanup: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function cleanupDriver(string $driver): int
    {
        $cache = Cache::manager();
        $repository = $cache->driver($driver); // Get repository

        if (method_exists($repository, 'cleanup')) {
            return $repository->cleanup();
        }

        // If cleanup is not supported, return 0
        return 0;
    }
}
