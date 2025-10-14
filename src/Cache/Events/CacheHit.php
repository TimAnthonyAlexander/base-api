<?php

namespace BaseApi\Cache\Events;

/**
 * Cache hit event.
 * 
 * Fired when a cache key is found and returned.
 */
class CacheHit
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly string $driver
    ) {}
}
