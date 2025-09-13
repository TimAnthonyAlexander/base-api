<?php

namespace BaseApi;

class Profiler
{
    private array $spans = [];
    private array $activeSpans = [];
    private bool $enabled = false;

    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
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
            $result = $callback();
            return $result;
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
     * Get profiling summary
     */
    public function getSummary(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $totalTime = array_sum(array_column($this->spans, 'duration_ms'));
        
        return [
            'total_spans' => count($this->spans),
            'total_time_ms' => round($totalTime, 3),
            'spans' => $this->spans,
        ];
    }

    /**
     * Reset all spans (useful for testing)
     */
    public function reset(): void
    {
        $this->spans = [];
        $this->activeSpans = [];
    }
}
