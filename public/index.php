<?php

require_once __DIR__ . '/../vendor/autoload.php';

use BaseApi\App;

// Boot the application
App::boot();

// Load routes
require_once __DIR__ . '/../routes/api.php';

// Handle the request
App::kernel()->handle();
