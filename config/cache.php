<?php

/**
 * Cache Configuration
 * 
 * This file contains the configuration for the BaseAPI unified caching system.
 * It supports multiple cache drivers including array (memory), file system, and Redis.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | cache manager when no specific store is requested. You may change this
    | to any of the stores defined in the "stores" array below.
    |
    */
    'default' => $_ENV['CACHE_DRIVER'] ?? 'file',

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache stores for your application as
    | well as their drivers. You can also define multiple stores for the
    | same cache driver to allow for fine-grained cache management.
    |
    */
    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => $_ENV['CACHE_PATH'] ?? null, // Uses storage/cache by default
            'permissions' => 0755,
        ],

        'redis' => [
            'driver' => 'redis',
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'database' => $_ENV['REDIS_CACHE_DB'] ?? 1,
            'timeout' => 5.0,
            'retry_interval' => 100,
            'read_timeout' => 60.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a cache store that supports multiple applications or
    | environments, you may specify a key prefix to prevent collisions
    | with other applications using the same cache backend.
    |
    */
    'prefix' => $_ENV['CACHE_PREFIX'] ?? 'baseapi_cache',

    /*
    |--------------------------------------------------------------------------
    | Default Cache TTL
    |--------------------------------------------------------------------------
    |
    | Default time-to-live (in seconds) for cache entries when no TTL is
    | explicitly specified. Set to null for no expiration by default.
    |
    */
    'default_ttl' => (int)($_ENV['CACHE_DEFAULT_TTL'] ?? 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Cache Serialization
    |--------------------------------------------------------------------------
    |
    | When using file or Redis cache, this setting controls whether values
    | should be serialized before storage. This allows storing complex
    | data types but adds some overhead.
    |
    */
    'serialize' => true,

    /*
    |--------------------------------------------------------------------------
    | Response Cache
    |--------------------------------------------------------------------------
    |
    | Settings for HTTP response caching middleware.
    |
    */
    'response_cache' => [
        'enabled' => $_ENV['CACHE_RESPONSES'] ?? false,
        'default_ttl' => (int)($_ENV['CACHE_RESPONSE_TTL'] ?? 600), // 10 minutes
        'prefix' => 'response',
        'vary_headers' => ['Accept', 'Accept-Encoding', 'Authorization'],
        'ignore_query_params' => ['_t', 'timestamp', 'cache_bust'],
    ],
];
