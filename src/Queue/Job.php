<?php

namespace BaseApi\Queue;

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
     * @return void
     * @throws \Throwable
     */
    abstract public function handle(): void;
    
    /**
     * Handle a job failure. Can be overridden by concrete job classes.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // Default failed job handling - log the error
        App::logger()->error("Job failed: " . get_class($this) . " - " . $exception->getMessage(), [
            'job_class' => get_class($this),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
    
    /**
     * Get the maximum number of retry attempts.
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }
    
    /**
     * Get the delay in seconds before retrying a failed job.
     *
     * @return int
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
}
