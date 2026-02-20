<?php

namespace BaseApi\Cache;

use Override;
use Exception;
use BaseApi\Cache\Stores\StoreInterface;

/**
 * Cache repository provides a unified interface to cache stores.
 * 
 * This class wraps store implementations and provides additional
 * functionality like prefix handling and tagged cache creation.
 */
class Repository implements CacheInterface
{
    public function __construct(private readonly StoreInterface $store, private readonly string $prefix = '') {}

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->store->get($this->prefixedKey($key));
        return $value ?? $default;
    }

    #[Override]
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $this->store->put($this->prefixedKey($key), $value, $ttl);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    #[Override]
    public function forget(string $key): bool
    {
        return $this->store->forget($this->prefixedKey($key));
    }

    #[Override]
    public function flush(): bool
    {
        return $this->store->flush();
    }

    #[Override]
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    #[Override]
    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, null);
    }

    #[Override]
    public function increment(string $key, int $value = 1): int
    {
        return $this->store->increment($this->prefixedKey($key), $value);
    }

    #[Override]
    public function decrement(string $key, int $value = 1): int
    {
        return $this->store->decrement($this->prefixedKey($key), $value);
    }

    #[Override]
    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this->store, $tags);
    }

    /**
     * Check if a key exists in the cache.
     */
    public function has(string $key): bool
    {
        return $this->store->has($this->prefixedKey($key));
    }

    /**
     * Get the underlying store instance.
     */
    public function getStore(): StoreInterface
    {
        return $this->store;
    }

    /**
     * Get the cache prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get multiple cache values.
     */
    public function many(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * Store multiple cache values.
     */
    public function putMany(array $values, ?int $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Store a value if the key doesn't exist.
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->put($key, $value, $ttl);
    }

    /**
     * Store a value and return the previous value.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Get and increment a counter atomically.
     */
    public function getAndIncrement(string $key, int $value = 1): int
    {
        $current = (int)$this->get($key, 0);
        $this->increment($key, $value);
        return $current;
    }

    /**
     * Get and decrement a counter atomically.
     */
    public function getAndDecrement(string $key, int $value = 1): int
    {
        $current = (int)$this->get($key, 0);
        $this->decrement($key, $value);
        return $current;
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        return $this->store->getStats();
    }

    /**
     * Clean up expired cache entries.
     */
    public function cleanup(): int
    {
        return $this->store->cleanup();
    }

    private function prefixedKey(string $key): string
    {
        // If the store has its own prefix, let it handle prefixing — don't add it here
        // too, or every key gets double-prefixed (store adds prefix in its own methods).
        if ($this->store->getPrefix() !== '' && $this->store->getPrefix() !== '0') {
            return $key;
        }

        return $this->prefix !== '' && $this->prefix !== '0' ? $this->prefix . ':' . $key : $key;
    }
}
