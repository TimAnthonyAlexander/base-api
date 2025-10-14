<?php

// Database Configuration Examples
// Copy this to your .env file and modify as needed

return [
    // MySQL Configuration
    'mysql' => [
        'DB_DRIVER' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'baseapi',
        'DB_USER' => 'root',
        'DB_PASSWORD' => '',
        'DB_CHARSET' => 'utf8mb4',
        'DB_PERSISTENT' => 'false',
    ],
    
    // SQLite Configuration
    'sqlite' => [
        'DB_DRIVER' => 'sqlite',
        'DB_NAME' => 'database.sqlite', // File path relative to storage/ or absolute path
        // For in-memory database, use:
        // 'DB_NAME' => ':memory:',
    ],
];
