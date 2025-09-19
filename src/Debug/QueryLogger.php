<?php

namespace BaseApi\Debug;

class QueryLogger
{
    private array $queries = [];
    private bool $enabled = false;
    private float $slowQueryThreshold = 100.0; // milliseconds

    public function __construct(bool $enabled = false, float $slowQueryThreshold = 100.0)
    {
        $this->enabled = $enabled;
        $this->slowQueryThreshold = $slowQueryThreshold;
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
     * Log a database query with timing and context
     */
    public function logQuery(string $sql, array $bindings = [], float $timeMs = 0.0, ?\Throwable $exception = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time_ms' => round($timeMs, 3),
            'slow' => $timeMs > $this->slowQueryThreshold,
            'exception' => $exception ? $exception->getMessage() : null,
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get all logged queries
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get queries that exceed the slow query threshold
     */
    public function getSlowQueries(): array
    {
        return array_filter($this->queries, fn($query) => $query['slow']);
    }

    /**
     * Get query statistics
     */
    public function getStats(): array
    {
        $totalTime = array_sum(array_column($this->queries, 'time_ms'));
        $slowQueries = $this->getSlowQueries();
        
        return [
            'total_queries' => count($this->queries),
            'total_time_ms' => round($totalTime, 3),
            'slow_queries' => count($slowQueries),
            'average_time_ms' => count($this->queries) > 0 ? round($totalTime / count($this->queries), 3) : 0,
        ];
    }

    /**
     * Clear all logged queries
     */
    public function clear(): void
    {
        $this->queries = [];
    }
}
