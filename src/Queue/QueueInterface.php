<?php

namespace BaseApi\Queue;

use Throwable;

/**
 * Interface for queue drivers that handle job storage and retrieval.
 */
interface QueueInterface
{
    /**
     * Push a job onto the queue.
     *
     * @return string The job ID
     */
    public function push(JobInterface $job, string $queue = 'default', int $delay = 0): string;

    /**
     * Pop the next job from the queue.
     */
    public function pop(string $queue = 'default'): ?QueueJob;

    /**
     * Retry a failed job.
     */
    public function retry(string $jobId): bool;

    /**
     * Mark a job as failed.
     */
    public function fail(string $jobId, Throwable $exception): bool;

    /**
     * Mark a job as completed.
     */
    public function complete(string $jobId): bool;

    /**
     * Get the size of the queue.
     */
    public function size(string $queue = 'default'): int;
}
