<?php

namespace BaseApi\Cache\Stores;

use BaseApi\Time\ClockInterface;
use BaseApi\Time\SystemClock;

/**
 * In-memory array cache store.
 * 
 * Provides fast caching for development and testing environments.
 * Data is only stored for the lifetime of the current process/request.
 */
class ArrayStore implements StoreInterface
{
    private array $storage = [];
    private string $prefix;
    private ClockInterface $clock;

    public function __construct(string $prefix = '', ?ClockInterface $clock = null)
    {
        $this->prefix = $prefix;
        $this->clock = $clock ?? new SystemClock();
    }

    public function get(string $key): mixed
    {
        $prefixedKey = $this->prefixedKey($key);
        
        if (!isset($this->storage[$prefixedKey])) {
            return null;
        }

        $item = $this->storage[$prefixedKey];
        
        // Check if expired
        if ($item['expires_at'] !== null && $item['expires_at'] < $this->clock->now()) {
            unset($this->storage[$prefixedKey]);
            return null;
        }

        return $item['value'];
    }

    public function put(string $key, mixed $value, ?int $seconds): void
    {
        $prefixedKey = $this->prefixedKey($key);
        $expiresAt = $seconds !== null ? $this->clock->now() + $seconds : null;

        $this->storage[$prefixedKey] = [
            'value' => $value,
            'expires_at' => $expiresAt,
        ];
    }

    public function forget(string $key): bool
    {
        $prefixedKey = $this->prefixedKey($key);
        
        if (isset($this->storage[$prefixedKey])) {
            unset($this->storage[$prefixedKey]);
            return true;
        }

        return false;
    }

    public function flush(): bool
    {
        $this->storage = [];
        return true;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function increment(string $key, int $value): int
    {
        $current = $this->get($key);
        $new = is_numeric($current) ? (int)$current + $value : $value;
        
        // Keep same TTL if exists
        $prefixedKey = $this->prefixedKey($key);
        $ttl = null;
        if (isset($this->storage[$prefixedKey])) {
            $expiresAt = $this->storage[$prefixedKey]['expires_at'];
            $ttl = $expiresAt ? max(0, $expiresAt - $this->clock->now()) : null;
        }
        
        $this->put($key, $new, $ttl);
        return $new;
    }

    public function decrement(string $key, int $value): int
    {
        return $this->increment($key, -$value);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get memory usage statistics for debugging.
     */
    public function getStats(): array
    {
        $totalItems = count($this->storage);
        $expiredItems = 0;
        $memoryUsage = 0;

        foreach ($this->storage as $item) {
            if ($item['expires_at'] !== null && $item['expires_at'] < $this->clock->now()) {
                $expiredItems++;
            }
            // Rough memory estimation
            $memoryUsage += strlen(serialize($item));
        }

        return [
            'total_items' => $totalItems,
            'expired_items' => $expiredItems,
            'active_items' => $totalItems - $expiredItems,
            'estimated_memory_bytes' => $memoryUsage,
        ];
    }

    /**
     * Clean up expired entries.
     */
    public function cleanup(): int
    {
        $removed = 0;
        $now = $this->clock->now();

        foreach ($this->storage as $key => $item) {
            if ($item['expires_at'] !== null && $item['expires_at'] < $now) {
                unset($this->storage[$key]);
                $removed++;
            }
        }

        return $removed;
    }

    private function prefixedKey(string $key): string
    {
        return $this->prefix ? $this->prefix . ':' . $key : $key;
    }
}
