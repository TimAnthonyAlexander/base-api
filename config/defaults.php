<?php

// Default framework configuration
// Applications can override these values in their config/app.php

return [
    'app' => [
        'env' => 'local',
        'debug' => true,
        'url' => 'http://127.0.0.1:7879',
        'host' => '127.0.0.1',
        'port' => 7879,
        'response_time' => false,
    ],

    'cors' => [
        'allowlist' => ['http://127.0.0.1:5173', 'http://localhost:5173'],
    ],

    'session' => [
        'name' => 'BASEAPISESSID',
        'samesite' => 'Lax',
        'secure' => false,
    ],

    'request' => [
        'max_json_mb' => 2,
    ],

    'upload' => [
        'max_mb' => 25,
        'allowed_mime' => ['image/jpeg', 'image/png', 'application/pdf'],
    ],

    'rate_limit' => [
        'dir' => 'storage/ratelimits',
        'trust_proxy' => false,
    ],

    'database' => [
        'host' => '127.0.0.1',
        'port' => 7878,
        'name' => 'baseapi',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'persistent' => false,
    ],

    'migrations' => [
        'file' => 'storage/migrations.json',
    ],

    'logging' => [
        'default' => 'file',
        'path' => 'storage/logs/baseapi.log',
        'level' => 'debug',
    ],

    'i18n' => [
        'default_lang' => 'en',
        'translations_dir' => 'translations',
        'public_access' => false,
        'cache_translations' => true,
    ],

    'filesystems' => [
        'default' => 'local',
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => 'storage/app',
                'url' => 'http://127.0.0.1:7879/storage',
            ],
            'public' => [
                'driver' => 'local',
                'root' => 'storage/app/public',
                'url' => 'http://127.0.0.1:7879/storage',
                'visibility' => 'public',
            ],
        ],
    ],

    'queue' => [
        'default' => 'sync',
        'drivers' => [
            'sync' => [
                'driver' => 'sync',
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
            ],
        ],
    ],

    'openai' => [
        'api_key' => getenv('OPENAI_API_KEY') ?: '',
        'default_model' => getenv('OPENAI_DEFAULT_MODEL') ?: 'gpt-4.1-mini',
        'temperature' => (float)(getenv('OPENAI_TEMPERATURE') ?: 1.0),
        'max_output_tokens' => (int)(getenv('OPENAI_MAX_TOKENS') ?: 1000),
        'timeout' => (int)(getenv('OPENAI_TIMEOUT') ?: 30),
        'max_retries' => (int)(getenv('OPENAI_MAX_RETRIES') ?: 3),
    ],
];
