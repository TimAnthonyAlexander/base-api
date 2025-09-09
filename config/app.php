<?php

// Optional application configuration defaults
// These can be overridden by environment variables

return [
    'app' => [
        'env' => 'local',
        'debug' => true,
        'url' => 'http://127.0.0.1:8000',
        'host' => '127.0.0.1',
        'port' => 8000,
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
];
