<?php

namespace BaseApi\Time;

/**
 * Clock interface for time-aware components.
 * 
 * Allows dependency injection of different time sources for production
 * and testing scenarios.
 */
interface ClockInterface
{
    /**
     * Get current Unix timestamp.
     */
    public function now(): int;
}
