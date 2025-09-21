<?php

namespace BaseApi\Queue;

/**
 * Interface for jobs that can be queued and processed asynchronously.
 */
interface JobInterface
{
    /**
     * Execute the job.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle(): void;

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void;

    /**
     * Get the maximum number of retry attempts.
     *
     * @return int
     */
    public function getMaxRetries(): int;

    /**
     * Get the delay in seconds before retrying a failed job.
     *
     * @return int
     */
    public function getRetryDelay(): int;
}
