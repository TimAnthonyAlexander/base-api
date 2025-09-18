<?php

namespace BaseApi\Cache\Events;

/**
 * Key written event.
 * 
 * Fired when a value is written to cache.
 */
class KeyWritten
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?int $ttl,
        public readonly string $driver
    ) {}
}
