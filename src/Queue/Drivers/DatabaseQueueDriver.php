<?php

namespace BaseApi\Queue\Drivers;

use Override;
use Exception;
use Throwable;
use BaseApi\Queue\QueueInterface;
use BaseApi\Queue\QueueJob;
use BaseApi\Queue\JobInterface;
use BaseApi\Database\Connection;
use BaseApi\Support\Uuid;

/**
 * Database-backed queue driver.
 */
class DatabaseQueueDriver implements QueueInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }
    
    /**
     * Push a job onto the queue.
     */
    #[Override]
    public function push(JobInterface $job, string $queue = 'default', int $delay = 0): string
    {
        $id = Uuid::v7();
        $runAt = $delay > 0 ? date('Y-m-d H:i:s', time() + $delay) : date('Y-m-d H:i:s');
        
        $this->connection->qb()
            ->table('jobs')
            ->insert([
                'id' => $id,
                'queue' => $queue,
                'payload' => serialize($job),
                'status' => 'pending',
                'run_at' => $runAt,
                'created_at' => date('Y-m-d H:i:s'),
                'attempts' => 0,
            ]);
        
        return $id;
    }
    
    /**
     * Pop the next job from the queue.
     */
    #[Override]
    public function pop(string $queue = 'default'): ?QueueJob
    {
        // Start transaction for atomic job claiming
        $this->connection->beginTransaction();
        
        try {
            $jobData = $this->connection->qb()
                ->table('jobs')
                ->where('queue', '=', $queue)
                ->where('status', '=', 'pending')
                ->where('run_at', '<=', date('Y-m-d H:i:s'))
                ->orderBy('created_at', 'ASC')
                ->limit(1)
                ->lockForUpdate()
                ->first();
            
            if (!$jobData) {
                $this->connection->rollback();
                return null;
            }
            
            // Mark job as processing
            $this->connection->qb()
                ->table('jobs')
                ->where('id', '=', $jobData['id'])
                ->update([
                    'status' => 'processing',
                    'started_at' => date('Y-m-d H:i:s'),
                    'attempts' => $jobData['attempts'] + 1,
                ]);
            
            $this->connection->commit();
            
            return new QueueJob($jobData['id'], unserialize($jobData['payload']));
        } catch (Exception $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }
    
    /**
     * Retry a failed job.
     */
    #[Override]
    public function retry(string $jobId): bool
    {
        $affected = $this->connection->qb()
            ->table('jobs')
            ->where('id', '=', $jobId)
            ->where('status', '=', 'failed')
            ->update([
                'status' => 'pending',
                'run_at' => date('Y-m-d H:i:s'),
                'error' => null,
                'failed_at' => null,
            ]);
        
        return $affected > 0;
    }
    
    /**
     * Mark a job as failed.
     */
    #[Override]
    public function fail(string $jobId, Throwable $exception): bool
    {
        $jobData = $this->connection->qb()
            ->table('jobs')
            ->where('id', '=', $jobId)
            ->first();
        
        if (!$jobData) {
            return false;
        }
        
        $job = unserialize($jobData['payload']);
        
        // Check if job should be retried
        if ($jobData['attempts'] < $job->getMaxRetries()) {
            // Schedule for retry
            $retryAt = date('Y-m-d H:i:s', time() + $job->getRetryDelay());
            
            $this->connection->qb()
                ->table('jobs')
                ->where('id', '=', $jobId)
                ->update([
                    'status' => 'pending',
                    'run_at' => $retryAt,
                    'error' => $exception->getMessage(),
                ]);
                
            return true;
        }
        
        // Mark as permanently failed
        $this->connection->qb()
            ->table('jobs')
            ->where('id', '=', $jobId)
            ->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'failed_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Call job's failed method
        $job->failed($exception);
        
        return false;
    }
    
    /**
     * Mark a job as completed.
     */
    #[Override]
    public function complete(string $jobId): bool
    {
        $affected = $this->connection->qb()
            ->table('jobs')
            ->where('id', '=', $jobId)
            ->update([
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
        
        return $affected > 0;
    }
    
    /**
     * Get the size of the queue.
     */
    #[Override]
    public function size(string $queue = 'default'): int
    {
        $result = $this->connection->qb()
            ->table('jobs')
            ->where('queue', '=', $queue)
            ->where('status', '=', 'pending')
            ->where('run_at', '<=', date('Y-m-d H:i:s'))
            ->count();
            
        return (int) $result;
    }
}
