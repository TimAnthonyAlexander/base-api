<?php

namespace BaseApi\Queue;

use BaseApi\App;

/**
 * Fluent interface for dispatching jobs with options.
 */
class PendingJob
{
    private JobInterface $job;
    private string $queue = 'default';
    private int $delay = 0;
    
    public function __construct(JobInterface $job)
    {
        $this->job = $job;
    }
    
    /**
     * Set the queue for this job.
     *
     * @param string $queue
     * @return self
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }
    
    /**
     * Set a delay for this job.
     *
     * @param int $seconds
     * @return self
     */
    public function delay(int $seconds): self
    {
        $this->delay = $seconds;
        return $this;
    }
    
    /**
     * Dispatch the job immediately.
     *
     * @return string Job ID
     */
    public function dispatch(): string
    {
        return App::queue()->push($this->job, $this->queue, $this->delay);
    }
}
