<?php

namespace BaseApi;

use BaseApi\Http\Kernel;
use BaseApi\Database\Connection;
use BaseApi\Database\DB;
use BaseApi\Auth\UserProvider;
use BaseApi\Auth\SimpleUserProvider;

class App
{
    private static ?Config $config = null;
    private static ?Logger $logger = null;
    private static ?Router $router = null;
    private static ?Kernel $kernel = null;
    private static ?Connection $connection = null;
    private static ?DB $db = null;
    private static ?UserProvider $userProvider = null;
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
        self::$connection = new Connection();
        self::$db = new DB(self::$connection);
        self::$userProvider = new SimpleUserProvider(self::$db);
        self::$kernel = new Kernel(self::$router);

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

    public static function db(): DB
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$db;
    }

    public static function userProvider(): UserProvider
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$userProvider;
    }
}
