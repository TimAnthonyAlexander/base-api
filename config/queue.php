<?php

/**
 * BaseAPI Queue Configuration
 * 
 * This configuration defines queue drivers for background job processing.
 * The queue system allows asynchronous processing of tasks like email
 * sending, image processing, and API calls.
 * 
 * Worker settings are configured via command-line options when running workers.
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
    |
    */
    'default' => $_ENV['QUEUE_DRIVER'] ?? 'sync',

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
        ],
    ],

];
