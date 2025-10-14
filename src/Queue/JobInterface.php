<?php

namespace BaseApi\Queue;

use Throwable;

/**
 * Interface for jobs that can be queued and processed asynchronously.
 */
interface JobInterface
{
    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void;

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void;

    /**
     * Get the maximum number of retry attempts.
     */
    public function getMaxRetries(): int;

    /**
     * Get the delay in seconds before retrying a failed job.
     */
    public function getRetryDelay(): int;
}
