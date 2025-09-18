<?php

namespace BaseApi\Cache\Stores;

/**
 * Interface for cache store implementations.
 * 
 * Store implementations handle the actual storage and retrieval of cache data
 * in different backends (array, file, Redis, etc.).
 */
interface StoreInterface
{
    /**
     * Get a cached value by key.
     */
    public function get(string $key): mixed;

    /**
     * Store a value with expiration time.
     * 
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|null $seconds TTL in seconds (null = no expiration)
     */
    public function put(string $key, mixed $value, ?int $seconds): void;

    /**
     * Remove a key from the cache.
     */
    public function forget(string $key): bool;

    /**
     * Clear all cached values.
     */
    public function flush(): bool;

    /**
     * Get the cache prefix used by this store.
     */
    public function getPrefix(): string;

    /**
     * Increment a numeric value.
     */
    public function increment(string $key, int $value): int;

    /**
     * Decrement a numeric value.
     */
    public function decrement(string $key, int $value): int;

    /**
     * Check if a key exists and is not expired.
     */
    public function has(string $key): bool;
}
