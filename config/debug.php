<?php

/**
 * Debug Configuration
 * 
 * Controls debugging and profiling features for development.
 * All debug features are automatically disabled in production.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debugging features. Only works in local/development environments.
    | Debug features will be completely disabled in production regardless 
    | of this setting for security.
    |
    */
    'enabled' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',

    /*
    |--------------------------------------------------------------------------
    | Profiler Settings
    |--------------------------------------------------------------------------
    |
    | Configure the profiler behavior including memory tracking,
    | query logging, and exception tracking.
    |
    */
    'profiler' => [
        'enabled' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'memory_tracking' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'query_logging' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'exception_tracking' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging
    |--------------------------------------------------------------------------
    |
    | Configure SQL query logging including slow query detection
    | and parameter binding logging.
    |
    */
    'queries' => [
        // Log all database queries when debugging is enabled
        'log_all' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        
        // Threshold in milliseconds for slow query detection
        'slow_query_threshold' => (int) ($_ENV['SLOW_QUERY_THRESHOLD'] ?? 100),
        
        // Include parameter bindings in query logs
        'log_bindings' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        
        // Include stack trace for query origin (performance impact)
        'log_stack_trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Panel
    |--------------------------------------------------------------------------
    |
    | Configure the debug panel display options for web requests.
    |
    */
    'panel' => [
        // Show debug panel in HTML responses when debugging is enabled
        'show_in_response' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        
        // Show floating debug toolbar (alternative to panel)
        'show_toolbar' => false,
        
        // Panel position: 'bottom', 'top'
        'panel_position' => 'bottom',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request/Response Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging of HTTP request and response data.
    |
    */
    'logging' => [
        // Log incoming request details when debugging is enabled
        'log_requests' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        
        // Log outgoing response details
        'log_responses' => false,
        
        // Fields to filter out from logs for security
        'sensitive_fields' => [
            'password', 
            'token', 
            'secret', 
            'key', 
            'auth',
            'authorization',
            'x-api-key',
            'cookie',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Tracking
    |--------------------------------------------------------------------------
    |
    | Configure memory usage monitoring and warnings.
    |
    */
    'memory' => [
        // Enable memory usage tracking when debugging is enabled
        'track_usage' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        
        // Memory usage threshold in MB for warnings
        'warning_threshold' => 128,
        
        // Track memory growth between snapshots
        'track_growth' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Warnings
    |--------------------------------------------------------------------------
    |
    | Configure automatic performance warning detection.
    |
    */
    'performance' => [
        // Enable automatic performance warnings  
        'enable_warnings' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        
        // Maximum acceptable query count per request
        'max_query_count' => 20,
        
        // Maximum acceptable request time in milliseconds
        'max_request_time' => 1000,
    ],

];
