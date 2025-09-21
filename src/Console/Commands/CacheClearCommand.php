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
                echo ColorHelper::info("🏷️  Clearing cache for tags: " . implode(', ', $tagsList)) . "\n";
                
                $cache = Cache::tags($tagsList);
                $result = $cache->flush();
                
                if ($result) {
                    echo ColorHelper::success("✅ Cache cleared for specified tags.") . "\n";
                } else {
                    echo ColorHelper::error("❌ Failed to clear cache for specified tags.") . "\n";
                    return 1;
                }
            } elseif ($driver) {
                // Clear specific driver
                echo ColorHelper::info(sprintf('📋 Clearing cache for driver: %s', $driver)) . "\n";
                
                $cache = Cache::driver($driver);
                $result = $cache->flush();
                
                if ($result) {
                    echo ColorHelper::success(sprintf('✅ Cache cleared for driver: %s', $driver)) . "\n";
                } else {
                    echo ColorHelper::error(sprintf('❌ Failed to clear cache for driver: %s', $driver)) . "\n";
                    return 1;
                }
            } else {
                // Clear all drivers
                echo ColorHelper::info("🧹 Clearing all cache...") . "\n";
                
                $config = App::config();
                $stores = $config->get('cache.stores', []);
                $cleared = 0;
                $failed = 0;
                
                foreach (array_keys($stores) as $storeName) {
                    try {
                        $cache = Cache::driver($storeName);
                        if ($cache->flush()) {
                            echo ColorHelper::success(sprintf('  ✓ Cleared %s', $storeName)) . "\n";
                            $cleared++;
                        } else {
                            echo ColorHelper::error(sprintf('  ✗ Failed to clear %s', $storeName)) . "\n";
                            $failed++;
                        }
                    } catch (Exception $e) {
                        echo ColorHelper::error(sprintf('  ✗ Error clearing %s: ', $storeName) . $e->getMessage()) . "\n";
                        $failed++;
                    }
                }
                
                if ($failed === 0) {
                    echo ColorHelper::success(sprintf('✅ All cache stores cleared successfully (%d stores).', $cleared)) . "\n";
                } else {
                    echo ColorHelper::warning(sprintf('⚠️  Cache clearing completed with %d failures and %d successes.', $failed, $cleared)) . "\n";
                    return 1;
                }
            }

            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error clearing cache: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}
