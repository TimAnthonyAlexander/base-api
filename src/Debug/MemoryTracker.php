<?php

namespace BaseApi\Debug;

class MemoryTracker
{
    private array $snapshots = [];
    private bool $enabled = false;
    private float $highMemoryThreshold = 128.0; // MB

    public function __construct(bool $enabled = false, float $highMemoryThreshold = 128.0)
    {
        $this->enabled = $enabled;
        $this->highMemoryThreshold = $highMemoryThreshold;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Take a memory snapshot at a specific point
     */
    public function snapshot(string $label): void
    {
        if (!$this->enabled) {
            return;
        }

        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $this->snapshots[] = [
            'label' => $label,
            'memory_mb' => round($currentMemory / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'memory_bytes' => $currentMemory,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get all memory snapshots
     */
    public function getSnapshots(): array
    {
        return $this->snapshots;
    }

    /**
     * Get memory usage statistics
     */
    public function getStats(): array
    {
        $currentMemory = memory_get_usage(true) / 1024 / 1024;
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
        
        return [
            'current_memory_mb' => round($currentMemory, 2),
            'peak_memory_mb' => round($peakMemory, 2),
            'high_memory_usage' => $peakMemory > $this->highMemoryThreshold,
            'snapshots_count' => count($this->snapshots),
            'memory_growth' => $this->calculateMemoryGrowth(),
        ];
    }

    /**
     * Calculate memory growth between first and last snapshot
     */
    private function calculateMemoryGrowth(): ?array
    {
        if (count($this->snapshots) < 2) {
            return null;
        }

        $first = $this->snapshots[0];
        $last = $this->snapshots[count($this->snapshots) - 1];
        
        $growthMb = $last['memory_mb'] - $first['memory_mb'];
        
        return [
            'growth_mb' => round($growthMb, 2),
            'growth_percentage' => $first['memory_mb'] > 0 ? round(($growthMb / $first['memory_mb']) * 100, 1) : 0,
        ];
    }

    /**
     * Get memory warnings
     */
    public function getWarnings(): array
    {
        $warnings = [];
        $stats = $this->getStats();
        
        if ($stats['high_memory_usage']) {
            $warnings[] = sprintf('High memory usage: %.2f MB (threshold: %.2f MB)', 
                $stats['peak_memory_mb'], $this->highMemoryThreshold);
        }
        
        $growth = $this->calculateMemoryGrowth();
        if ($growth && $growth['growth_mb'] > 50) {
            $warnings[] = sprintf('Significant memory growth: +%.2f MB (%+.1f%%)', 
                $growth['growth_mb'], $growth['growth_percentage']);
        }
        
        return $warnings;
    }

    /**
     * Clear all snapshots
     */
    public function clear(): void
    {
        $this->snapshots = [];
    }
}
