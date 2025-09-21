<?php

namespace BaseApi;

use Throwable;

class Profiler
{
    private array $spans = [];

    private array $activeSpans = [];

    private array $queries = [];

    private array $exceptions = [];

    private array $memorySnapshots = [];

    private array $requests = [];

    private array $responses = [];

    public function __construct(private bool $enabled = false)
    {
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
     * Start a new profiling span
     */
    public function start(string $name, array $metadata = []): string
    {
        if (!$this->enabled) {
            return '';
        }

        $spanId = uniqid('span_', true);
        $startTime = hrtime(true);

        $this->activeSpans[$spanId] = [
            'name' => $name,
            'start_time' => $startTime,
            'metadata' => $metadata,
        ];

        return $spanId;
    }

    /**
     * Stop a profiling span
     */
    public function stop(string $spanId): void
    {
        if (!$this->enabled || !isset($this->activeSpans[$spanId])) {
            return;
        }

        $endTime = hrtime(true);
        $span = $this->activeSpans[$spanId];

        $this->spans[] = [
            'name' => $span['name'],
            'duration_ms' => round(($endTime - $span['start_time']) / 1_000_000, 3),
            'metadata' => $span['metadata'],
        ];

        unset($this->activeSpans[$spanId]);
    }

    /**
     * Profile a callable and return its result
     */
    public function profile(string $name, callable $callback, array $metadata = []): mixed
    {
        $spanId = $this->start($name, $metadata);

        try {
            return $callback();
        } finally {
            $this->stop($spanId);
        }
    }

    /**
     * Get all completed spans
     */
    public function getSpans(): array
    {
        return $this->spans;
    }


    /**
     * Log a SQL query with timing and context
     */
    public function logQuery(string $query, array $bindings = [], float $time = 0.0, ?Throwable $exception = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[] = [
            'query' => $query,
            'bindings' => $bindings,
            'time_ms' => round($time, 3),
            'exception' => $exception instanceof Throwable ? $exception->getMessage() : null,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
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
     * Get slow queries above threshold
     */
    public function getSlowQueries(float $threshold = 100.0): array
    {
        return array_filter($this->queries, fn($query): bool => $query['time_ms'] > $threshold);
    }

    /**
     * Track memory usage at a specific point
     */
    public function trackMemory(string $label): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->memorySnapshots[] = [
            'label' => $label,
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get memory usage snapshots
     */
    public function getMemoryUsage(): array
    {
        return $this->memorySnapshots;
    }

    /**
     * Log an exception with context
     */
    public function logException(Throwable $exception, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->exceptions[] = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'class' => $exception::class,
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get all logged exceptions
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * Log request information
     */
    public function logRequest($request): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->requests[] = [
            'method' => is_object($request) && method_exists($request, 'method') ? $request->method : 'UNKNOWN',
            'url' => is_object($request) && method_exists($request, 'url') ? $request->url() : 'UNKNOWN',
            'headers' => is_object($request) && property_exists($request, 'headers') ? $request->headers : [],
            'body' => is_object($request) && method_exists($request, 'body') ? $request->body() : null,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Log response information
     */
    public function logResponse($response): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->responses[] = [
            'status' => is_object($response) && property_exists($response, 'status') ? $response->status : 200,
            'headers' => is_object($response) && property_exists($response, 'headers') ? $response->headers : [],
            'body_size' => is_object($response) && property_exists($response, 'body') ? strlen($response->body) : 0,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get performance warnings based on collected data
     */
    public function getPerformanceWarnings(): array
    {
        $warnings = [];

        // Check for slow queries
        $slowQueries = $this->getSlowQueries(100.0);
        if ($slowQueries !== []) {
            $warnings[] = sprintf('Found %d slow queries (>100ms)', count($slowQueries));
        }

        // Check memory usage
        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;
        if ($memoryPeak > 128) {
            $warnings[] = sprintf('High memory usage: %.2f MB', $memoryPeak);
        }

        // Check total query count
        if (count($this->queries) > 20) {
            $warnings[] = sprintf('High query count: %d queries executed', count($this->queries));
        }

        return $warnings;
    }

    /**
     * Get enhanced profiling summary
     */
    public function getSummary(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $totalTime = array_sum(array_column($this->spans, 'duration_ms'));
        $queryTime = array_sum(array_column($this->queries, 'time_ms'));

        return [
            'request' => [
                'total_time_ms' => round($totalTime, 3),
                'query_time_ms' => round($queryTime, 3),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'query_count' => count($this->queries),
            ],
            'spans' => $this->spans,
            'queries' => $this->queries,
            'slow_queries' => $this->getSlowQueries(),
            'memory_snapshots' => $this->memorySnapshots,
            'exceptions' => $this->exceptions,
            'warnings' => $this->getPerformanceWarnings(),
        ];
    }

    /**
     * Reset all profiling data
     */
    public function reset(): void
    {
        $this->spans = [];
        $this->activeSpans = [];
        $this->queries = [];
        $this->exceptions = [];
        $this->memorySnapshots = [];
        $this->requests = [];
        $this->responses = [];
    }
}
