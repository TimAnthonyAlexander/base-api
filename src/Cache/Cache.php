<?php

namespace BaseApi\Cache;

use BaseApi\App;

/**
 * Cache facade providing static access to cache functionality.
 * 
 * This class provides a convenient static interface to the cache system,
 * allowing easy access from anywhere in the application.
 */
class Cache
{
    private static ?CacheManager $manager = null;

    /**
     * Get the cache manager instance.
     */
    public static function manager(): CacheManager
    {
        if (!self::$manager instanceof CacheManager) {
            self::$manager = App::container()->make(CacheManager::class);
        }

        return self::$manager;
    }

    /**
     * Get a cache store instance.
     */
    public static function driver(?string $name = null): CacheInterface
    {
        return self::manager()->driver($name);
    }

    /**
     * Get a cached value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::manager()->get($key, $default);
    }

    /**
     * Store a value in cache.
     */
    public static function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return self::manager()->put($key, $value, $ttl);
    }

    /**
     * Remove a key from cache.
     */
    public static function forget(string $key): bool
    {
        return self::manager()->forget($key);
    }

    /**
     * Clear all cache.
     */
    public static function flush(): bool
    {
        return self::manager()->flush();
    }

    /**
     * Get a cached value or store the result of a callback.
     * @param callable(): mixed $callback
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return self::manager()->remember($key, $ttl, $callback);
    }

    /**
     * Store a value permanently.
     */
    public static function forever(string $key, mixed $value): bool
    {
        return self::manager()->forever($key, $value);
    }

    /**
     * Increment a numeric cache value.
     */
    public static function increment(string $key, int $value = 1): int
    {
        return self::manager()->increment($key, $value);
    }

    /**
     * Decrement a numeric cache value.
     */
    public static function decrement(string $key, int $value = 1): int
    {
        return self::manager()->decrement($key, $value);
    }

    /**
     * Create a tagged cache instance.
     * @param array<string> $tags
     */
    public static function tags(array $tags): TaggedCache
    {
        return self::manager()->tags($tags);
    }

    /**
     * Register a custom cache driver.
     * @param callable(): mixed $callback
     */
    public static function extend(string $driver, callable $callback): void
    {
        self::manager()->extend($driver, $callback);
    }

    /**
     * Purge a cache store.
     */
    public static function purge(?string $name = null): void
    {
        self::manager()->purge($name);
    }

    /**
     * Check if a key exists in cache.
     */
    public static function has(string $key): bool
    {
        $repository = self::manager()->driver();
        
        if (method_exists($repository, 'has')) {
            return $repository->has($key);
        }
        
        return self::get($key) !== null;
    }

    /**
     * Get multiple cache values.
     * @param array<string> $keys
     * @return array<string, mixed>
     */
    public static function many(array $keys): array
    {
        $repository = self::manager()->driver();
        
        if (method_exists($repository, 'many')) {
            return $repository->many($keys);
        }
        
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = self::get($key);
        }
        
        return $values;
    }

    /**
     * Store multiple cache values.
     * @param array<string, mixed> $values
     */
    public static function putMany(array $values, ?int $ttl = null): bool
    {
        $repository = self::manager()->driver();
        
        if (method_exists($repository, 'putMany')) {
            return $repository->putMany($values, $ttl);
        }
        
        $success = true;
        foreach ($values as $key => $value) {
            if (!self::put($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Store a value if key doesn't exist.
     */
    public static function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (self::has($key)) {
            return false;
        }
        
        return self::put($key, $value, $ttl);
    }

    /**
     * Get and remove a value from cache.
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);
        return $value;
    }

    /**
     * Get cache statistics.
     * @return array<string, mixed>
     */
    public static function stats(?string $driver = null): array
    {
        $repository = self::manager()->driver($driver);
        
        if (method_exists($repository, 'getStats')) {
            return $repository->getStats();
        }
        
        return [];
    }

    /**
     * Clean up expired cache entries.
     */
    public static function cleanup(?string $driver = null): int
    {
        $repository = self::manager()->driver($driver);
        
        if (method_exists($repository, 'cleanup')) {
            return $repository->cleanup();
        }
        
        return 0;
    }

    /**
     * Generate a cache key from components.
     */
    public static function key(mixed ...$components): string
    {
        $parts = [];
        
        foreach ($components as $component) {
            $parts[] = is_array($component) ? md5(serialize($component)) : (string)$component;
        }
        
        return implode(':', $parts);
    }

    /**
     * Reset the manager instance (useful for testing).
     */
    public static function reset(): void
    {
        self::$manager = null;
    }
}
