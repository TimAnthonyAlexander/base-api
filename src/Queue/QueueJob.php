<?php

namespace BaseApi\Queue;

/**
 * Wrapper class for a job that has been pulled from the queue.
 */
class QueueJob
{
    public function __construct(
        private string $id,
        private JobInterface $job
    ) {}
    
    /**
     * Get the job ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Get the job instance.
     *
     * @return JobInterface
     */
    public function getJob(): JobInterface
    {
        return $this->job;
    }
}
