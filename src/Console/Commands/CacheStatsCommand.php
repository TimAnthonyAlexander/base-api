<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Cache\Cache;
use BaseApi\App;

/**
 * Show cache statistics command.
 */
class CacheStatsCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'cache:stats';
    }

    #[Override]
    public function description(): string
    {
        return 'Show cache statistics';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $driver = $args[0] ?? null;
        $config = App::config();

        try {
            if ($driver) {
                // Show stats for specific driver
                $this->showDriverStats($driver);
            } else {
                // Show stats for all configured drivers
                $stores = $config->get('cache.stores', []);
                $defaultDriver = $config->get('cache.default', 'array');
                
                echo "Cache Configuration:\n";
                echo sprintf('Default driver: %s%s', $defaultDriver, PHP_EOL);
                echo "Configured stores: " . implode(', ', array_keys($stores)) . "\n";
                echo "\n";

                foreach (array_keys($stores) as $storeName) {
                    $this->showDriverStats($storeName);
                    echo "\n";
                }
            }

            return 0;
        } catch (Exception $exception) {
            echo "❌ Error getting cache stats: " . $exception->getMessage() . "\n";
            return 1;
        }
    }

    private function showDriverStats(string $driver): void
    {
        echo sprintf('Cache Driver: %s%s', $driver, PHP_EOL);
        
        try {
            $cache = Cache::manager();
            $repository = $cache->driver($driver); // Get repository
            
            if (method_exists($repository, 'getStats')) {
                $stats = $repository->getStats();
                
                if (empty($stats)) {
                    echo "  No statistics available for this driver\n";
                    return;
                }

                // Format and display stats based on driver type
                $this->displayFormattedStats($driver, $stats);
            } else {
                echo "  Statistics not supported for this driver\n";
            }
        } catch (Exception $exception) {
            echo "  Error: " . $exception->getMessage() . "\n";
        }
    }

    private function displayFormattedStats(string $driver, array $stats): void
    {
        // Format stats based on driver type
        if ($driver === 'array') {
            $this->displayArrayStats($stats);
        } elseif ($driver === 'file') {
            $this->displayFileStats($stats);
        } elseif ($driver === 'redis') {
            $this->displayRedisStats($stats);
        } else {
            // Generic stats display
            $this->displayGenericStats($stats);
        }
    }

    private function displayArrayStats(array $stats): void
    {
        echo "  Type: In-Memory Array\n";
        echo "  Total Items: " . ($stats['total_items'] ?? 0) . "\n";
        echo "  Active Items: " . ($stats['active_items'] ?? 0) . "\n";
        echo "  Expired Items: " . ($stats['expired_items'] ?? 0) . "\n";
        echo "  Estimated Memory: " . $this->formatBytes($stats['estimated_memory_bytes'] ?? 0) . "\n";
    }

    private function displayFileStats(array $stats): void
    {
        echo "  Type: File System\n";
        echo "  Total Files: " . ($stats['total_files'] ?? 0) . "\n";
        echo "  Active Files: " . ($stats['active_files'] ?? 0) . "\n";
        echo "  Expired Files: " . ($stats['expired_files'] ?? 0) . "\n";
        echo "  Total Size: " . $this->formatBytes($stats['total_size_bytes'] ?? 0) . "\n";
    }

    private function displayRedisStats(array $stats): void
    {
        echo "  Type: Redis\n";
        echo "  Connected Clients: " . ($stats['connected_clients'] ?? 'N/A') . "\n";
        echo "  Used Memory: " . ($stats['used_memory_human'] ?? $this->formatBytes($stats['used_memory'] ?? 0)) . "\n";
        
        if (isset($stats['keyspace_hits']) && isset($stats['keyspace_misses'])) {
            $hits = (int)$stats['keyspace_hits'];
            $misses = (int)$stats['keyspace_misses'];
            $total = $hits + $misses;
            $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
            
            echo sprintf('  Cache Hits: %s%s', $hits, PHP_EOL);
            echo sprintf('  Cache Misses: %s%s', $misses, PHP_EOL);
            echo "  Hit Rate: {$hitRate}%\n";
        }
        
        if (isset($stats['total_commands_processed'])) {
            echo "  Total Commands: " . number_format($stats['total_commands_processed']) . "\n";
        }
    }

    private function displayGenericStats(array $stats): void
    {
        echo "  Type: Generic\n";
        foreach ($stats as $key => $value) {
            $formattedKey = ucwords(str_replace(['_', '-'], ' ', $key));
            $formattedValue = is_numeric($value) ? number_format($value) : $value;
            echo sprintf('  %s: %s%s', $formattedKey, $formattedValue, PHP_EOL);
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
