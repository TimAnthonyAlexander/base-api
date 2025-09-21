<?php

namespace BaseApi\Time;

/**
 * System clock using real time.
 * 
 * Production implementation that uses PHP's time() function.
 */
class SystemClock implements ClockInterface
{
    public function now(): int
    {
        return time();
    }
}
