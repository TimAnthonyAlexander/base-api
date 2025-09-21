<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Cache\Cache;
use BaseApi\App;

/**
 * Clear cache entries command.
 */
class CacheClearCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'cache:clear';
    }

    #[Override]
    public function description(): string
    {
        return 'Clear cache entries';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        // Parse arguments
        $driver = $args[0] ?? null;
        $tags = null;
        
        // Parse options (simple implementation)
        foreach ($args as $arg) {
            if (str_starts_with((string) $arg, '--tags=')) {
                $tags = substr((string) $arg, 7);
                break;
            }
        }

        try {
            if ($tags) {
                // Clear by tags
                $tagsList = array_map('trim', explode(',', $tags));
                echo "Clearing cache for tags: " . implode(', ', $tagsList) . "\n";
                
                $cache = Cache::tags($tagsList);
                $result = $cache->flush();
                
                if ($result) {
                    echo "✅ Cache cleared for specified tags.\n";
                } else {
                    echo "❌ Failed to clear cache for specified tags.\n";
                    return 1;
                }
            } elseif ($driver) {
                // Clear specific driver
                echo sprintf('Clearing cache for driver: %s%s', $driver, PHP_EOL);
                
                $cache = Cache::driver($driver);
                $result = $cache->flush();
                
                if ($result) {
                    echo sprintf('✅ Cache cleared for driver: %s%s', $driver, PHP_EOL);
                } else {
                    echo sprintf('❌ Failed to clear cache for driver: %s%s', $driver, PHP_EOL);
                    return 1;
                }
            } else {
                // Clear all drivers
                echo "Clearing all cache...\n";
                
                $config = App::config();
                $stores = $config->get('cache.stores', []);
                $cleared = 0;
                $failed = 0;
                
                foreach (array_keys($stores) as $storeName) {
                    try {
                        $cache = Cache::driver($storeName);
                        if ($cache->flush()) {
                            echo sprintf('✓ Cleared %s%s', $storeName, PHP_EOL);
                            $cleared++;
                        } else {
                            echo sprintf('✗ Failed to clear %s%s', $storeName, PHP_EOL);
                            $failed++;
                        }
                    } catch (Exception $e) {
                        echo sprintf('✗ Error clearing %s: ', $storeName) . $e->getMessage() . "\n";
                        $failed++;
                    }
                }
                
                if ($failed === 0) {
                    echo "✅ All cache stores cleared successfully ({$cleared} stores).\n";
                } else {
                    echo "⚠️ Cache clearing completed with {$failed} failures and {$cleared} successes.\n";
                    return $failed > 0 ? 1 : 0;
                }
            }

            return 0;
        } catch (Exception $exception) {
            echo "❌ Error clearing cache: " . $exception->getMessage() . "\n";
            return 1;
        }
    }
}
