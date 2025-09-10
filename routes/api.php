<?php

use BaseApi\App;
use BaseApi\Controllers\HealthController;
use BaseApi\Controllers\TestController;
use BaseApi\Controllers\UserController;
use BaseApi\Controllers\GetOnlyController;
use BaseApi\Http\Middleware\RateLimitMiddleware;

$router = App::router();

// Health check endpoint with rate limiting (60 requests per minute)
$router->get('/health', [RateLimitMiddleware::class => ['limit' => '60/1m'], HealthController::class]);
$router->post('/health', [HealthController::class]);

// Test endpoints for Milestone 2
$router->get('/test', [TestController::class]);
$router->post('/test', [TestController::class]);

// User endpoints with route parameters
$router->get('/users', [UserController::class]);
$router->get('/users/{id}', [UserController::class]);
$router->delete('/users/{id}', [UserController::class]);

// Test 405 Method Not Allowed
$router->get('/getonly', [GetOnlyController::class]);
$router->post('/getonly', [GetOnlyController::class]);
