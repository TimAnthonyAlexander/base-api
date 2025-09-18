<?php

namespace BaseApi\Cache\Stores;

/**
 * Redis-based cache store.
 * 
 * Provides distributed caching using Redis for multi-server deployments.
 * Supports connection pooling and automatic retry logic.
 */
class RedisStore implements StoreInterface
{
    private ?\Redis $redis = null;
    private array $config;
    private string $prefix;

    public function __construct(array $config = [], string $prefix = '')
    {
        $this->config = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'timeout' => 5.0,
            'retry_interval' => 100,
            'read_timeout' => 60.0,
        ], $config);
        
        $this->prefix = $prefix;
    }

    public function get(string $key): mixed
    {
        $redis = $this->getRedis();
        $prefixedKey = $this->prefixedKey($key);
        
        try {
            $value = $redis->get($prefixedKey);
            
            if ($value === false) {
                return null;
            }

            return $this->unserialize($value);
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            return null;
        }
    }

    public function put(string $key, mixed $value, ?int $seconds): void
    {
        $redis = $this->getRedis();
        $prefixedKey = $this->prefixedKey($key);
        $serialized = $this->serialize($value);

        try {
            if ($seconds === null) {
                $redis->set($prefixedKey, $serialized);
            } else {
                $redis->setex($prefixedKey, $seconds, $serialized);
            }
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            throw new \RuntimeException("Failed to store cache value: " . $e->getMessage(), 0, $e);
        }
    }

    public function forget(string $key): bool
    {
        $redis = $this->getRedis();
        $prefixedKey = $this->prefixedKey($key);

        try {
            return $redis->del($prefixedKey) > 0;
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            return false;
        }
    }

    public function flush(): bool
    {
        $redis = $this->getRedis();

        try {
            if ($this->prefix) {
                // Only flush keys with our prefix
                $pattern = $this->prefixedKey('*');
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    return $redis->del($keys) > 0;
                }
                
                return true;
            } else {
                // Flush entire database
                return $redis->flushDB();
            }
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            return false;
        }
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function increment(string $key, int $value): int
    {
        $redis = $this->getRedis();
        $prefixedKey = $this->prefixedKey($key);

        try {
            if ($value === 1) {
                return $redis->incr($prefixedKey);
            } else {
                return $redis->incrBy($prefixedKey, $value);
            }
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            
            // Fallback: get current value and set new one
            $current = $this->get($key);
            $new = is_numeric($current) ? (int)$current + $value : $value;
            $this->put($key, $new, null);
            
            return $new;
        }
    }

    public function decrement(string $key, int $value): int
    {
        $redis = $this->getRedis();
        $prefixedKey = $this->prefixedKey($key);

        try {
            if ($value === 1) {
                return $redis->decr($prefixedKey);
            } else {
                return $redis->decrBy($prefixedKey, $value);
            }
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            
            // Fallback: get current value and set new one
            $current = $this->get($key);
            $new = is_numeric($current) ? (int)$current - $value : -$value;
            $this->put($key, $new, null);
            
            return $new;
        }
    }

    public function has(string $key): bool
    {
        $redis = $this->getRedis();
        $prefixedKey = $this->prefixedKey($key);

        try {
            return $redis->exists($prefixedKey) > 0;
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            return false;
        }
    }

    /**
     * Get Redis connection statistics.
     */
    public function getStats(): array
    {
        $redis = $this->getRedis();

        try {
            $info = $redis->info();
            
            return [
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            ];
        } catch (\RedisException $e) {
            $this->handleConnectionError($e);
            return [];
        }
    }

    /**
     * Test Redis connection.
     */
    public function ping(): bool
    {
        try {
            $redis = $this->getRedis();
            return $redis->ping() !== false;
        } catch (\RedisException $e) {
            return false;
        }
    }

    private function getRedis(): \Redis
    {
        if ($this->redis === null || !$this->redis->isConnected()) {
            $this->connect();
        }

        return $this->redis;
    }

    private function connect(): void
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis PHP extension is not installed');
        }

        $this->redis = new \Redis();

        $connected = $this->redis->connect(
            $this->config['host'],
            $this->config['port'],
            $this->config['timeout'],
            null,
            $this->config['retry_interval'],
            $this->config['read_timeout']
        );

        if (!$connected) {
            throw new \RuntimeException("Failed to connect to Redis server at {$this->config['host']}:{$this->config['port']}");
        }

        // Authenticate if password is provided
        if ($this->config['password'] !== null) {
            if (!$this->redis->auth($this->config['password'])) {
                throw new \RuntimeException('Redis authentication failed');
            }
        }

        // Select database
        if ($this->config['database'] !== 0) {
            $this->redis->select($this->config['database']);
        }

        // Set serialization options
        $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    }

    private function prefixedKey(string $key): string
    {
        return $this->prefix ? $this->prefix . ':' . $key : $key;
    }

    private function serialize(mixed $value): string
    {
        // Redis extension handles serialization when OPT_SERIALIZER is set
        return is_numeric($value) && !is_string($value) ? (string)$value : serialize($value);
    }

    private function unserialize(string $value): mixed
    {
        // Try to unserialize, fall back to raw value for simple types
        $unserialized = @unserialize($value);
        return $unserialized !== false ? $unserialized : $value;
    }

    private function handleConnectionError(\RedisException $e): void
    {
        // Reset connection on error
        $this->redis = null;
        
        // Log the error
        error_log("Redis connection error: " . $e->getMessage());
    }
}
