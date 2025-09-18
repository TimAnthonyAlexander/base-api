<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Cache\Cache;
use BaseApi\App;

/**
 * Clear cache entries command.
 */
class CacheClearCommand implements Command
{
    public function name(): string
    {
        return 'cache:clear';
    }

    public function description(): string
    {
        return 'Clear cache entries';
    }

    public function execute(array $args, ?Application $app = null): int
    {
        // Parse arguments
        $driver = $args[0] ?? null;
        $tags = null;
        
        // Parse options (simple implementation)
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--tags=')) {
                $tags = substr($arg, 7);
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
                echo "Clearing cache for driver: {$driver}\n";
                
                $cache = Cache::driver($driver);
                $result = $cache->flush();
                
                if ($result) {
                    echo "✅ Cache cleared for driver: {$driver}\n";
                } else {
                    echo "❌ Failed to clear cache for driver: {$driver}\n";
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
                            echo "✓ Cleared {$storeName}\n";
                            $cleared++;
                        } else {
                            echo "✗ Failed to clear {$storeName}\n";
                            $failed++;
                        }
                    } catch (\Exception $e) {
                        echo "✗ Error clearing {$storeName}: " . $e->getMessage() . "\n";
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
        } catch (\Exception $e) {
            echo "❌ Error clearing cache: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}
