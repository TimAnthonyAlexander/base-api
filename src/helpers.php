<?php

use BaseApi\App;
use BaseApi\Queue\JobInterface;
use BaseApi\Queue\PendingJob;

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage directory.
     * 
     * @param string $path Path within storage directory
     * @return string Full path to storage location
     */
    function storage_path(string $path = ''): string
    {
        return App::storagePath($path);
    }
}

if (!function_exists('dispatch')) {
    /**
     * Dispatch a job to the queue.
     * 
     * @param JobInterface $job The job to dispatch
     * @return string Job ID
     */
    function dispatch(JobInterface $job): string
    {
        return App::queue()->push($job, 'default', 0);
    }
}

if (!function_exists('dispatch_later')) {
    /**
     * Create a pending job for fluent dispatch configuration.
     * 
     * @param JobInterface $job The job to dispatch
     * @return PendingJob Fluent interface for setting options
     */
    function dispatch_later(JobInterface $job): PendingJob
    {
        return new PendingJob($job);
    }
}

