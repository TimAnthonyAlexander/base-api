<?php

namespace BaseApi\Cache;

/**
 * Cache interface providing unified caching functionality across different drivers.
 */
interface CacheInterface 
{
    /**
     * Get a cached value by key.
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in cache with optional TTL.
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null = forever)
     * @return bool True if stored successfully
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Remove a key from cache.
     * 
     * @param string $key Cache key to remove
     * @return bool True if removed successfully
     */
    public function forget(string $key): bool;

    /**
     * Clear all cache entries.
     * 
     * @return bool True if cleared successfully
     */
    public function flush(): bool;

    /**
     * Get a cached value or store the result of a callback.
     * 
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Callback to execute if cache miss
     * @return mixed The cached value or callback result
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Store a value permanently (no expiration).
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return bool True if stored successfully
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Increment a numeric cache value.
     * 
     * @param string $key Cache key
     * @param int $value Amount to increment
     * @return int The new value after increment
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * Decrement a numeric cache value.
     * 
     * @param string $key Cache key
     * @param int $value Amount to decrement
     * @return int The new value after decrement
     */
    public function decrement(string $key, int $value = 1): int;

    /**
     * Create a tagged cache instance for invalidation support.
     * 
     * @param array $tags Array of tags to associate with cache entries
     * @return TaggedCache Tagged cache instance
     */
    public function tags(array $tags): TaggedCache;
}
