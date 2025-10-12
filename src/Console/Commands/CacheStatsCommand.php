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
                
                echo ColorHelper::header("📊 Cache Configuration") . "\n";
                echo str_repeat('─', 80) . "\n";
                echo ColorHelper::info("Default driver: ") . ColorHelper::colorize($defaultDriver, ColorHelper::YELLOW) . "\n";
                echo ColorHelper::info("Configured stores: ") . ColorHelper::colorize(implode(', ', array_keys($stores)), ColorHelper::YELLOW) . "\n";
                echo "\n";

                foreach (array_keys($stores) as $storeName) {
                    $this->showDriverStats($storeName);
                    echo "\n";
                }
            }

            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error getting cache stats: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function showDriverStats(string $driver): void
    {
        echo ColorHelper::header(sprintf('📊 Cache Driver: %s', $driver)) . "\n";
        echo str_repeat('─', 80) . "\n";
        
        try {
            $cache = Cache::manager();
            $repository = $cache->driver($driver); // Get repository
            
            if (method_exists($repository, 'getStats')) {
                $stats = $repository->getStats();
                
                if (empty($stats)) {
                    echo ColorHelper::comment("  No statistics available for this driver") . "\n";
                    return;
                }

                // Format and display stats based on driver type
                $this->displayFormattedStats($driver, $stats);
            } else {
                echo ColorHelper::comment("  Statistics not supported for this driver") . "\n";
            }
        } catch (Exception $exception) {
            echo ColorHelper::error("  Error: " . $exception->getMessage()) . "\n";
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
        echo ColorHelper::info("  Type: ") . ColorHelper::colorize("In-Memory Array", ColorHelper::CYAN) . "\n";
        echo ColorHelper::info("  Total Items: ") . ColorHelper::colorize((string)($stats['total_items'] ?? 0), ColorHelper::YELLOW) . "\n";
        echo ColorHelper::info("  Active Items: ") . ColorHelper::colorize((string)($stats['active_items'] ?? 0), ColorHelper::GREEN) . "\n";
        echo ColorHelper::info("  Expired Items: ") . ColorHelper::colorize((string)($stats['expired_items'] ?? 0), ColorHelper::RED) . "\n";
        echo ColorHelper::info("  Estimated Memory: ") . ColorHelper::colorize($this->formatBytes($stats['estimated_memory_bytes'] ?? 0), ColorHelper::MAGENTA) . "\n";
    }

    private function displayFileStats(array $stats): void
    {
        echo ColorHelper::info("  Type: ") . ColorHelper::colorize("File System", ColorHelper::CYAN) . "\n";
        echo ColorHelper::info("  Total Files: ") . ColorHelper::colorize((string)($stats['total_files'] ?? 0), ColorHelper::YELLOW) . "\n";
        echo ColorHelper::info("  Active Files: ") . ColorHelper::colorize((string)($stats['active_files'] ?? 0), ColorHelper::GREEN) . "\n";
        echo ColorHelper::info("  Expired Files: ") . ColorHelper::colorize((string)($stats['expired_files'] ?? 0), ColorHelper::RED) . "\n";
        echo ColorHelper::info("  Total Size: ") . ColorHelper::colorize($this->formatBytes($stats['total_size_bytes'] ?? 0), ColorHelper::MAGENTA) . "\n";
    }

    private function displayRedisStats(array $stats): void
    {
        echo ColorHelper::info("  Type: ") . ColorHelper::colorize("Redis", ColorHelper::CYAN) . "\n";
        echo ColorHelper::info("  Connected Clients: ") . ColorHelper::colorize((string)($stats['connected_clients'] ?? 'N/A'), ColorHelper::YELLOW) . "\n";
        echo ColorHelper::info("  Used Memory: ") . ColorHelper::colorize($stats['used_memory_human'] ?? $this->formatBytes($stats['used_memory'] ?? 0), ColorHelper::MAGENTA) . "\n";
        
        if (isset($stats['keyspace_hits']) && isset($stats['keyspace_misses'])) {
            $hits = (int)$stats['keyspace_hits'];
            $misses = (int)$stats['keyspace_misses'];
            $total = $hits + $misses;
            $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
            
            echo ColorHelper::info("  Cache Hits: ") . ColorHelper::colorize((string)$hits, ColorHelper::GREEN) . "\n";
            echo ColorHelper::info("  Cache Misses: ") . ColorHelper::colorize((string)$misses, ColorHelper::RED) . "\n";
            echo ColorHelper::info("  Hit Rate: ") . ColorHelper::colorize($hitRate . '%', $hitRate > 80 ? ColorHelper::GREEN : ($hitRate > 60 ? ColorHelper::YELLOW : ColorHelper::RED)) . "\n";
        }
        
        if (isset($stats['total_commands_processed'])) {
            echo ColorHelper::info("  Total Commands: ") . ColorHelper::colorize(number_format($stats['total_commands_processed']), ColorHelper::YELLOW) . "\n";
        }
    }

    private function displayGenericStats(array $stats): void
    {
        echo ColorHelper::info("  Type: ") . ColorHelper::colorize("Generic", ColorHelper::CYAN) . "\n";
        foreach ($stats as $key => $value) {
            $formattedKey = ucwords(str_replace(['_', '-'], ' ', $key));
            $formattedValue = is_numeric($value) ? number_format($value) : $value;
            echo ColorHelper::info(sprintf('  %s: ', $formattedKey)) . ColorHelper::colorize((string)$formattedValue, ColorHelper::YELLOW) . "\n";
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
