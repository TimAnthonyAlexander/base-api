<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver is available out of the box.
    |
    */
    
    'default' => $_ENV['FILESYSTEM_DISK'] ?? 'local',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'url' => ($_ENV['APP_URL'] ?? 'http://localhost:8000') . '/storage',
            'permissions' => [
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ],
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => ($_ENV['APP_URL'] ?? 'http://localhost:8000') . '/storage',
            'visibility' => 'public',
        ],

        // Example S3 configuration (uncomment and configure when needed)
        /*
        's3' => [
            'driver' => 's3',
            'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? null,
            'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null,
            'region' => $_ENV['AWS_DEFAULT_REGION'] ?? 'us-east-1',
            'bucket' => $_ENV['AWS_BUCKET'] ?? null,
            'url' => $_ENV['AWS_URL'] ?? null,
            'endpoint' => $_ENV['AWS_ENDPOINT'] ?? null, // For S3-compatible services
            'use_path_style_endpoint' => $_ENV['AWS_USE_PATH_STYLE_ENDPOINT'] ?? false,
        ],
        */

        // Example Google Cloud Storage configuration
        /*
        'gcs' => [
            'driver' => 'gcs',
            'project_id' => $_ENV['GOOGLE_CLOUD_PROJECT_ID'] ?? null,
            'key_file' => $_ENV['GOOGLE_CLOUD_KEY_FILE'] ?? null, // Path to service account JSON
            'bucket' => $_ENV['GOOGLE_CLOUD_STORAGE_BUCKET'] ?? null,
            'path_prefix' => $_ENV['GOOGLE_CLOUD_STORAGE_PATH_PREFIX'] ?? null,
        ],
        */

    ],

];
