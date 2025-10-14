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

        // Note: S3 and Google Cloud Storage drivers are planned but not yet implemented
        // Only local filesystem driver is currently supported

    ],

];
