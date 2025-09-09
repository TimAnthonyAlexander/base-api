<?php

// Router script for the built-in PHP server
// If the requested file exists, let PHP serve it directly
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    
    if (is_file($file)) {
        return false; // Let PHP serve the static file
    }
}

// Otherwise, handle through our application
require_once __DIR__ . '/index.php';
