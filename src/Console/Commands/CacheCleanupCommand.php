<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Cache\Cache;
use BaseApi\App;

/**
 * Clean up expired cache entries command.
 */
class CacheCleanupCommand implements Command
{
    public function name(): string
    {
        return 'cache:cleanup';
    }

    public function description(): string
    {
        return 'Clean up expired cache entries';
    }

    public function execute(array $args, ?Application $app = null): int
    {
        $driver = $args[0] ?? null;
        $config = App::config();

        try {
            if ($driver) {
                // Cleanup specific driver
                $removed = $this->cleanupDriver($driver);
                echo "✅ Cleaned up {$removed} expired entries from {$driver} cache.\n";
            } else {
                // Cleanup all drivers that support it
                $stores = $config->get('cache.stores', []);
                $totalRemoved = 0;
                $driversProcessed = 0;

                echo "Cleaning up expired cache entries...\n";

                foreach (array_keys($stores) as $storeName) {
                    try {
                        $removed = $this->cleanupDriver($storeName);
                        if ($removed > 0) {
                            echo "✓ {$storeName}: removed {$removed} expired entries\n";
                            $totalRemoved += $removed;
                        } else {
                            echo "✓ {$storeName}: no expired entries found\n";
                        }
                        $driversProcessed++;
                    } catch (\Exception $e) {
                        echo "✗ {$storeName}: " . $e->getMessage() . "\n";
                    }
                }

                if ($totalRemoved > 0) {
                    echo "✅ Cleanup completed. Removed {$totalRemoved} expired entries from {$driversProcessed} drivers.\n";
                } else {
                    echo "ℹ️ Cleanup completed. No expired entries found in {$driversProcessed} drivers.\n";
                }
            }

            return 0;
        } catch (\Exception $e) {
            echo "❌ Error during cache cleanup: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function cleanupDriver(string $driver): int
    {
        $cache = Cache::driver($driver);
        $repository = $cache->driver(); // Get repository
        
        if (method_exists($repository, 'cleanup')) {
            return $repository->cleanup();
        }
        
        // If cleanup is not supported, return 0
        return 0;
    }
}
