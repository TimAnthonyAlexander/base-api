<?php

/**
 * BaseAPI Queue Configuration
 * 
 * This configuration defines queue drivers and worker settings for background
 * job processing. The queue system allows asynchronous processing of tasks
 * like email sending, image processing, and API calls.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | The default queue driver to use when none is specified. Available drivers:
    | - 'sync': Execute jobs immediately (good for development/testing)
    | - 'database': Store jobs in database (recommended for production)
    | - 'redis': Store jobs in Redis (high performance, requires Redis)
    |
    */
    'default' => env('QUEUE_DRIVER', 'sync'),
    
    /*
    |--------------------------------------------------------------------------
    | Queue Drivers
    |--------------------------------------------------------------------------
    |
    | Configuration for each queue driver. Each driver can have multiple
    | named connections with different settings.
    |
    */
    'drivers' => [
        'sync' => [
            'driver' => 'sync',
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'connection' => env('QUEUE_DB_CONNECTION', 'default'),
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'prefix' => env('QUEUE_REDIS_PREFIX', 'baseapi_queue:'),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for queue workers. These can be overridden using
    | command-line options when running workers.
    |
    */
    'worker' => [
        // Default sleep time in seconds between queue checks
        'sleep' => env('QUEUE_WORKER_SLEEP', 3),
        
        // Maximum number of jobs to process before restarting worker
        'max_jobs' => env('QUEUE_WORKER_MAX_JOBS', 1000),
        
        // Maximum time in seconds before restarting worker
        'max_time' => env('QUEUE_WORKER_MAX_TIME', 3600),
        
        // Memory limit in MB before restarting worker
        'memory_limit' => env('QUEUE_WORKER_MEMORY', 128),
        
        // Default timeout for job execution in seconds
        'timeout' => env('QUEUE_WORKER_TIMEOUT', 60),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Failed Job Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for handling failed jobs, including retention and cleanup.
    |
    */
    'failed' => [
        // Number of days to retain failed jobs before cleanup
        'retention_days' => env('QUEUE_FAILED_RETENTION', 30),
        
        // Whether to enable automatic cleanup of old failed jobs
        'cleanup_enabled' => env('QUEUE_FAILED_CLEANUP', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Batch Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for job batching (future enhancement).
    |
    */
    'batches' => [
        // Table to store batch information
        'table' => 'job_batches',
        
        // Default batch timeout in seconds
        'timeout' => 3600,
    ],
];
