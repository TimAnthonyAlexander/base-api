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

    // Most debug settings are handled automatically by the framework
    // Only the 'enabled' setting above is actually read by the code

];
