<?php

use BaseApi\App;
use BaseApi\Controllers\HealthController;
use BaseApi\Controllers\UserController;
use BaseApi\Controllers\LoginController;
use BaseApi\Controllers\LogoutController;
use BaseApi\Controllers\MeController;
use BaseApi\Http\Middleware\RateLimitMiddleware;
use BaseApi\Http\Middleware\AuthMiddleware;

$router = App::router();

$router->get(
    '/health',
    [
        RateLimitMiddleware::class => ['limit' => '60/1m'],
        HealthController::class,
    ],
);
$router->post(
    '/health',
    [
        HealthController::class,
    ],
);
$router->get(
    '/users',
    [
        UserController::class,
    ],
);
$router->get(
    '/users/{id}',
    [
        UserController::class,
    ],
);
$router->delete(
    '/users/{id}',
    [
        UserController::class,
    ],
);
$router->post(
    '/auth/login',
    [
        LoginController::class,
    ],
);
$router->post(
    '/auth/logout',
    [
        AuthMiddleware::class,
        LogoutController::class,
    ],
);
$router->get(
    '/me',
    [
        AuthMiddleware::class,
        MeController::class,
    ],
);
