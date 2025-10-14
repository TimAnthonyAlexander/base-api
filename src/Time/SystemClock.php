<?php

namespace BaseApi\Time;

use Override;

/**
 * System clock using real time.
 * 
 * Production implementation that uses PHP's time() function.
 */
class SystemClock implements ClockInterface
{
    #[Override]
    public function now(): int
    {
        return time();
    }
}
