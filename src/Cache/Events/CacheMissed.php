<?php

namespace BaseApi\Cache\Events;

/**
 * Cache missed event.
 * 
 * Fired when a cache key is requested but not found.
 */
class CacheMissed
{
    public function __construct(
        public readonly string $key,
        public readonly string $driver
    ) {}
}
