<?php

use BaseApi\App;
use BaseApi\Controllers\HealthController;

$router = App::router();

// Health check endpoint
$router->get('/health', [HealthController::class]);
