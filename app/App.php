<?php

namespace BaseApi;

use BaseApi\Http\Kernel;

class App
{
    private static ?Config $config = null;
    private static ?Logger $logger = null;
    private static ?Router $router = null;
    private static ?Kernel $kernel = null;
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        // Load environment
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();

        // Initialize services
        self::$config = new Config($_ENV);
        self::$logger = new Logger();
        self::$router = new Router();
        self::$kernel = new Kernel(self::$router, self::$config, self::$logger);

        // Register global middleware in order
        self::$kernel->addGlobal(\BaseApi\Http\ErrorHandler::class);
        self::$kernel->addGlobal(\BaseApi\Http\RequestIdMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\CorsMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\JsonBodyParserMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\SessionStartMiddleware::class);

        self::$booted = true;
    }

    public static function config(): Config
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$config;
    }

    public static function logger(): Logger
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$logger;
    }

    public static function router(): Router
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$router;
    }

    public static function kernel(): Kernel
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$kernel;
    }
}
