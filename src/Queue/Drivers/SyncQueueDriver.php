<?php

namespace BaseApi\Queue\Drivers;

use BaseApi\Queue\QueueInterface;
use BaseApi\Queue\QueueJob;
use BaseApi\Queue\JobInterface;
use BaseApi\Support\Uuid;

/**
 * Synchronous queue driver that executes jobs immediately.
 * Useful for testing and development.
 */
class SyncQueueDriver implements QueueInterface
{
    /**
     * Push a job onto the queue (executes immediately).
     *
     * @param JobInterface $job
     * @param string $queue
     * @param int $delay
     * @return string
     */
    public function push(JobInterface $job, string $queue = 'default', int $delay = 0): string
    {
        $id = Uuid::v7();
        
        // Execute the job immediately
        try {
            $job->handle();
        } catch (\Throwable $e) {
            $job->failed($e);
            throw $e;
        }
        
        return $id;
    }
    
    /**
     * Pop the next job from the queue (always returns null for sync driver).
     *
     * @param string $queue
     * @return QueueJob|null
     */
    public function pop(string $queue = 'default'): ?QueueJob
    {
        return null;
    }
    
    /**
     * Retry a failed job.
     *
     * @param string $jobId
     * @return bool
     */
    public function retry(string $jobId): bool
    {
        return false;
    }
    
    /**
     * Mark a job as failed.
     *
     * @param string $jobId
     * @param \Throwable $exception
     * @return bool
     */
    public function fail(string $jobId, \Throwable $exception): bool
    {
        return false;
    }
    
    /**
     * Mark a job as completed.
     *
     * @param string $jobId
     * @return bool
     */
    public function complete(string $jobId): bool
    {
        return true;
    }
    
    /**
     * Get the size of the queue (always returns 0 for sync driver).
     *
     * @param string $queue
     * @return int
     */
    public function size(string $queue = 'default'): int
    {
        return 0;
    }
}
