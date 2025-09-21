<?php

namespace BaseApi\Queue;

use BaseApi\Queue\Exceptions\JobFailedException;
use BaseApi\App;

/**
 * Queue worker that processes jobs from the queue.
 */
class QueueWorker
{
    private QueueInterface $queue;
    private bool $shouldStop = false;
    
    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
        
        // Handle graceful shutdown signals if running in CLI
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'stop']);
            pcntl_signal(SIGINT, [$this, 'stop']);
        }
    }
    
    /**
     * Start processing jobs from the queue.
     *
     * @param string $queueName
     * @param int $sleep
     * @param int $maxJobs
     * @param int $maxTime
     * @param int $memoryLimit
     * @return void
     */
    public function work(
        string $queueName = 'default',
        int $sleep = 5,
        int $maxJobs = 0,
        int $maxTime = 0,
        int $memoryLimit = 128
    ): void {
        $processedJobs = 0;
        $startTime = time();
        $memoryLimitBytes = $memoryLimit * 1024 * 1024;
        
        echo "Queue worker started for queue: {$queueName}\n";
        echo "Sleep: {$sleep}s, Max jobs: " . ($maxJobs ?: 'unlimited') . ", Max time: " . ($maxTime ?: 'unlimited') . "s, Memory limit: {$memoryLimit}MB\n\n";
        
        while (!$this->shouldStop) {
            // Dispatch signals if available
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            // Check memory limit
            if (memory_get_usage(true) > $memoryLimitBytes) {
                echo "Memory limit exceeded (" . round(memory_get_usage(true) / 1024 / 1024, 2) . "MB), stopping worker.\n";
                break;
            }
            
            // Check time limit
            if ($maxTime > 0 && (time() - $startTime) >= $maxTime) {
                echo "Time limit exceeded ({$maxTime}s), stopping worker.\n";
                break;
            }
            
            // Check max jobs limit
            if ($maxJobs > 0 && $processedJobs >= $maxJobs) {
                echo "Max jobs limit reached ({$maxJobs}), stopping worker.\n";
                break;
            }
            
            $job = $this->queue->pop($queueName);
            
            if ($job === null) {
                sleep($sleep);
                continue;
            }
            
            echo "[" . date('Y-m-d H:i:s') . "] Processing job: " . get_class($job->getJob()) . " (ID: " . $job->getId() . ")\n";
            
            try {
                $this->processJob($job);
                $this->queue->complete($job->getId());
                $processedJobs++;
                echo "[" . date('Y-m-d H:i:s') . "] Job completed successfully\n";
            } catch (\Throwable $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Job failed: " . $e->getMessage() . "\n";
                $this->queue->fail($job->getId(), $e);
            }
            
            // Small delay to prevent tight loops
            if ($sleep > 0) {
                usleep(100000); // 0.1 second
            }
        }
        
        echo "\nQueue worker stopped after processing {$processedJobs} jobs.\n";
    }
    
    /**
     * Process a single job.
     *
     * @param QueueJob $queueJob
     * @return void
     * @throws \Throwable
     */
    private function processJob(QueueJob $queueJob): void
    {
        $job = $queueJob->getJob();
        
        try {
            $job->handle();
        } catch (\Throwable $e) {
            // Log the exception before re-throwing
            App::logger()->error("Job processing failed", [
                'job_id' => $queueJob->getId(),
                'job_class' => get_class($job),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new JobFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Stop the worker gracefully.
     *
     * @return void
     */
    public function stop(): void
    {
        echo "\nReceived stop signal, finishing current job and shutting down...\n";
        $this->shouldStop = true;
    }
}
