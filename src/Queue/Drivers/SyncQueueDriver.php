<?php

namespace BaseApi\Queue\Drivers;

use Override;
use Throwable;
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
     */
    #[Override]
    public function push(JobInterface $job, string $queue = 'default', int $delay = 0): string
    {
        $id = Uuid::v7();
        
        // Execute the job immediately
        try {
            $job->handle();
        } catch (Throwable $throwable) {
            $job->failed($throwable);
            throw $throwable;
        }
        
        return $id;
    }
    
    /**
     * Pop the next job from the queue (always returns null for sync driver).
     */
    #[Override]
    public function pop(string $queue = 'default'): ?QueueJob
    {
        return null;
    }
    
    /**
     * Retry a failed job.
     */
    #[Override]
    public function retry(string $jobId): bool
    {
        return false;
    }
    
    /**
     * Mark a job as failed.
     */
    #[Override]
    public function fail(string $jobId, Throwable $exception): bool
    {
        return false;
    }
    
    /**
     * Mark a job as completed.
     */
    #[Override]
    public function complete(string $jobId): bool
    {
        return true;
    }
    
    /**
     * Get the size of the queue (always returns 0 for sync driver).
     */
    #[Override]
    public function size(string $queue = 'default'): int
    {
        return 0;
    }
}
