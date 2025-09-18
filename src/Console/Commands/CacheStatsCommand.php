<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Cache\Cache;
use BaseApi\App;

/**
 * Show cache statistics command.
 */
class CacheStatsCommand implements Command
{
    public function name(): string
    {
        return 'cache:stats';
    }

    public function description(): string
    {
        return 'Show cache statistics';
    }

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
                echo "Default driver: {$defaultDriver}\n";
                echo "Configured stores: " . implode(', ', array_keys($stores)) . "\n";
                echo "\n";

                foreach (array_keys($stores) as $storeName) {
                    $this->showDriverStats($storeName);
                    echo "\n";
                }
            }

            return 0;
        } catch (\Exception $e) {
            echo "❌ Error getting cache stats: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function showDriverStats(string $driver): void
    {
        echo "Cache Driver: {$driver}\n";
        
        try {
            $cache = Cache::driver($driver);
            $repository = $cache->driver(); // Get repository
            
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
        } catch (\Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
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
            $hits = $stats['keyspace_hits'];
            $misses = $stats['keyspace_misses'];
            $total = $hits + $misses;
            $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
            
            echo "  Cache Hits: {$hits}\n";
            echo "  Cache Misses: {$misses}\n";
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
            echo "  {$formattedKey}: {$formattedValue}\n";
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
