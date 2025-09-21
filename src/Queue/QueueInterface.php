<?php

namespace BaseApi\Queue;

/**
 * Interface for queue drivers that handle job storage and retrieval.
 */
interface QueueInterface
{
    /**
     * Push a job onto the queue.
     *
     * @param JobInterface $job
     * @param string $queue
     * @param int $delay
     * @return string The job ID
     */
    public function push(JobInterface $job, string $queue = 'default', int $delay = 0): string;

    /**
     * Pop the next job from the queue.
     *
     * @param string $queue
     * @return QueueJob|null
     */
    public function pop(string $queue = 'default'): ?QueueJob;

    /**
     * Retry a failed job.
     *
     * @param string $jobId
     * @return bool
     */
    public function retry(string $jobId): bool;

    /**
     * Mark a job as failed.
     *
     * @param string $jobId
     * @param \Throwable $exception
     * @return bool
     */
    public function fail(string $jobId, \Throwable $exception): bool;

    /**
     * Mark a job as completed.
     *
     * @param string $jobId
     * @return bool
     */
    public function complete(string $jobId): bool;

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     * @return int
     */
    public function size(string $queue = 'default'): int;
}
