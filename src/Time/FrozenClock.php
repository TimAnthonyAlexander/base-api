<?php

namespace BaseApi\Time;

use Override;

/**
 * Frozen clock for testing.
 * 
 * Allows controlling time in tests without using sleep() or actual time delays.
 */
class FrozenClock implements ClockInterface
{
    private int $time;

    public function __construct(?int $time = null)
    {
        $this->time = $time ?? time();
    }

    #[Override]
    public function now(): int
    {
        return $this->time;
    }

    /**
     * Set the current time.
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }

    /**
     * Advance time by the specified number of seconds.
     */
    public function advance(int $seconds): void
    {
        $this->time += $seconds;
    }

    /**
     * Go back in time by the specified number of seconds.
     */
    public function rewind(int $seconds): void
    {
        $this->time -= $seconds;
    }
}
