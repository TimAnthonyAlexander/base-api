<?php

namespace BaseApi\Queue;

use Override;
use Throwable;
use BaseApi\App;

/**
 * Base job class that implements common job functionality.
 */
abstract class Job implements JobInterface
{
    protected int $maxRetries = 3;

    protected int $retryDelay = 30; // seconds
    /**
     * Execute the job. Must be implemented by concrete job classes.
     *
     * @throws Throwable
     */
    #[Override]
    abstract public function handle(): void;

    /**
     * Handle a job failure. Can be overridden by concrete job classes.
     */
    #[Override]
    public function failed(Throwable $exception): void
    {
        // Default failed job handling - log the error
        App::logger()->error("Job failed: " . static::class . " - " . $exception->getMessage(), [
            'job_class' => static::class,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the maximum number of retry attempts.
     */
    #[Override]
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Get the delay in seconds before retrying a failed job.
     */
    #[Override]
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
}
