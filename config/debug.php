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
    'enabled' => env('DEBUG_ENABLED', env('APP_DEBUG', false)),

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
        'enabled' => env('PROFILER_ENABLED', true),
        'memory_tracking' => env('PROFILER_MEMORY', true),
        'query_logging' => env('PROFILER_QUERIES', true),
        'exception_tracking' => env('PROFILER_EXCEPTIONS', true),
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
        // Log all database queries
        'log_all' => env('DEBUG_LOG_QUERIES', true),
        
        // Threshold in milliseconds for slow query detection
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 100),
        
        // Include parameter bindings in query logs
        'log_bindings' => env('DEBUG_LOG_QUERY_BINDINGS', true),
        
        // Include stack trace for query origin (performance impact)
        'log_stack_trace' => env('DEBUG_LOG_STACK_TRACE', false),
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
        // Show debug panel in HTML responses
        'show_in_response' => env('DEBUG_SHOW_PANEL', true),
        
        // Show floating debug toolbar (alternative to panel)
        'show_toolbar' => env('DEBUG_SHOW_TOOLBAR', false),
        
        // Panel position: 'bottom', 'top'
        'panel_position' => env('DEBUG_PANEL_POSITION', 'bottom'),
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
        // Log incoming request details
        'log_requests' => env('DEBUG_LOG_REQUESTS', true),
        
        // Log outgoing response details
        'log_responses' => env('DEBUG_LOG_RESPONSES', false),
        
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
        // Enable memory usage tracking
        'track_usage' => env('DEBUG_TRACK_MEMORY', true),
        
        // Memory usage threshold in MB for warnings
        'warning_threshold' => env('DEBUG_MEMORY_WARNING', 128),
        
        // Track memory growth between snapshots
        'track_growth' => env('DEBUG_TRACK_MEMORY_GROWTH', true),
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
        'enable_warnings' => env('DEBUG_PERFORMANCE_WARNINGS', true),
        
        // Maximum acceptable query count per request
        'max_query_count' => env('DEBUG_MAX_QUERIES', 20),
        
        // Maximum acceptable request time in milliseconds
        'max_request_time' => env('DEBUG_MAX_REQUEST_TIME', 1000),
    ],

];
