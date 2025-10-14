<?php

namespace BaseApi\Cache;

use Override;
use InvalidArgumentException;
use BaseApi\App;
use BaseApi\Config;
use BaseApi\Cache\Stores\StoreInterface;
use BaseApi\Cache\Stores\ArrayStore;
use BaseApi\Cache\Stores\FileStore;
use BaseApi\Cache\Stores\RedisStore;

/**
 * Cache manager handles driver resolution and provides unified cache interface.
 */
class CacheManager implements CacheInterface
{
    /** @var array<string, CacheInterface> */
    private array $stores = [];

    /** @var array<string, callable(): CacheInterface> */
    private array $customDrivers = [];

    private ?string $defaultDriver = null;

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Get a cache store instance.
     */
    public function driver(?string $name = null): CacheInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->resolve($name);
        }

        return $this->stores[$name];
    }

    /**
     * Get the default cache driver name.
     */
    public function getDefaultDriver(): string
    {
        if ($this->defaultDriver === null) {
            $this->defaultDriver = $this->config->get('cache.default', 'array');
        }

        return $this->defaultDriver;
    }

    /**
     * Set the default cache driver.
     */
    public function setDefaultDriver(string $name): void
    {
        $this->defaultDriver = $name;
    }

    /**
     * Register a custom cache driver.
     * @param callable(): CacheInterface $callback
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customDrivers[$driver] = $callback;
    }

    /**
     * Purge a cache store instance.
     */
    public function purge(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultDriver();

        if (isset($this->stores[$name])) {
            $this->stores[$name]->flush();
            unset($this->stores[$name]);
        }
    }

    /**
     * Get all configured store names.
     */
    public function getStoreNames(): array
    {
        $stores = $this->config->get('cache.stores', []);
        return array_keys($stores);
    }

    // Implement CacheInterface methods (delegate to default driver)

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver()->get($key, $default);
    }

    #[Override]
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->driver()->put($key, $value, $ttl);
    }

    #[Override]
    public function forget(string $key): bool
    {
        return $this->driver()->forget($key);
    }

    #[Override]
    public function flush(): bool
    {
        return $this->driver()->flush();
    }

    #[Override]
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->driver()->remember($key, $ttl, $callback);
    }

    #[Override]
    public function forever(string $key, mixed $value): bool
    {
        return $this->driver()->forever($key, $value);
    }

    #[Override]
    public function increment(string $key, int $value = 1): int
    {
        return $this->driver()->increment($key, $value);
    }

    #[Override]
    public function decrement(string $key, int $value = 1): int
    {
        return $this->driver()->decrement($key, $value);
    }

    #[Override]
    public function tags(array $tags): TaggedCache
    {
        return $this->driver()->tags($tags);
    }

    /**
     * Resolve a cache store instance.
     */
    protected function resolve(string $name): CacheInterface
    {
        $config = $this->config->get('cache.stores.' . $name);

        if (!$config) {
            throw new InvalidArgumentException(sprintf('Cache store [%s] is not defined.', $name));
        }

        $driverName = $config['driver'] ?? $name;

        // Check for custom driver
        if (isset($this->customDrivers[$driverName])) {
            $store = call_user_func($this->customDrivers[$driverName], $config);
        } else {
            $store = $this->createStore($driverName, $config);
        }

        return new Repository($store, $this->getGlobalPrefix());
    }

    /**
     * Create a cache store instance.
     */
    protected function createStore(string $driver, array $config): StoreInterface
    {
        $method = 'create' . ucfirst($driver) . 'Store';

        if (method_exists($this, $method)) {
            return $this->{$method}($config);
        }

        throw new InvalidArgumentException(sprintf('Cache driver [%s] is not supported.', $driver));
    }

    /**
     * Create an array cache store.
     */
    protected function createArrayStore(array $config): ArrayStore
    {
        return new ArrayStore($this->getStorePrefix($config));
    }

    /**
     * Create a file cache store.
     */
    protected function createFileStore(array $config): FileStore
    {
        $path = $config['path'] ?? $this->getDefaultCachePath();
        $permissions = $config['permissions'] ?? 0755;

        return new FileStore($path, $this->getStorePrefix($config), $permissions);
    }

    /**
     * Create a Redis cache store.
     */
    protected function createRedisStore(array $config): RedisStore
    {
        $redisConfig = [
            'host' => $config['host'] ?? $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => $config['port'] ?? $_ENV['REDIS_PORT'] ?? 6379,
            'password' => $config['password'] ?? $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => $config['database'] ?? $_ENV['REDIS_CACHE_DB'] ?? 0,
            'timeout' => $config['timeout'] ?? 5.0,
            'retry_interval' => $config['retry_interval'] ?? 100,
            'read_timeout' => $config['read_timeout'] ?? 60.0,
        ];

        return new RedisStore($redisConfig, $this->getStorePrefix($config));
    }

    /**
     * Get the cache store prefix.
     */
    protected function getStorePrefix(array $config): string
    {
        return $config['prefix'] ?? $this->getGlobalPrefix();
    }

    /**
     * Get the global cache prefix.
     */
    protected function getGlobalPrefix(): string
    {
        return $this->config->get('cache.prefix', 'baseapi_cache');
    }

    /**
     * Get default cache path for file store.
     */
    protected function getDefaultCachePath(): string
    {
        // Use application's storage path instead of framework path
        return App::storagePath('cache');
    }
}
