<?php

namespace BaseApi\Queue;

/**
 * Wrapper class for a job that has been pulled from the queue.
 */
class QueueJob
{
    public function __construct(
        private readonly string $id,
        private readonly JobInterface $job
    ) {}
    
    /**
     * Get the job ID.
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Get the job instance.
     */
    public function getJob(): JobInterface
    {
        return $this->job;
    }
}
