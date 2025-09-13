<?php

namespace BaseApi;

use BaseApi\Http\Kernel;
use BaseApi\Database\Connection;
use BaseApi\Database\DB;
use BaseApi\Auth\UserProvider;

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
    private static ?string $basePath = null;

    public static function boot(?string $basePath = null): void
    {
        if (self::$booted) {
            return;
        }

        // Set base path - either provided or auto-detect
        if ($basePath) {
            self::$basePath = $basePath;
        } else {
            self::$basePath = self::detectBasePath();
        }

        // Load environment
        $dotenv = \Dotenv\Dotenv::createImmutable(self::$basePath);
        $dotenv->safeLoad();

        // Load framework defaults
        $frameworkDefaults = require __DIR__ . '/../config/defaults.php';

        // Load application configuration
        $appConfig = [];
        $configFile = self::$basePath . '/config/app.php';
        if (file_exists($configFile)) {
            $appConfig = require $configFile;
        }

        // Merge configurations: framework defaults < app config (app config overrides)
        $config = array_replace_recursive($frameworkDefaults, $appConfig);

        // Initialize services
        self::$config = new Config($config, $_ENV);
        self::$logger = new Logger();
        self::$router = new Router();
        self::$connection = new Connection();
        self::$db = new DB(self::$connection);
        self::$kernel = new Kernel(self::$router);

        // Register global middleware in order
        self::$kernel->addGlobal(\BaseApi\Http\ResponseTimeMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\ErrorHandler::class);
        self::$kernel->addGlobal(\BaseApi\Http\RequestIdMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\CorsMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\JsonBodyParserMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\FormBodyParserMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\SessionStartMiddleware::class);
        self::$kernel->addGlobal(\BaseApi\Http\SecurityHeadersMiddleware::class);

        self::$booted = true;
    }

    public static function config(string $key = '', mixed $default = null): mixed
    {
        if (!self::$booted) {
            self::boot();
        }

        if (empty($key)) {
            return self::$config;
        }

        return self::$config->get($key, $default);
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

    public static function setUserProvider(UserProvider $provider): void
    {
        if (!self::$booted) {
            self::boot();
        }
        self::$userProvider = $provider;
    }

    public static function userProvider(): UserProvider
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$userProvider;
    }

    public static function basePath(string $path = ''): string
    {
        if (!self::$booted) {
            self::boot();
        }

        $basePath = self::$basePath ?? self::detectBasePath();
        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }

    private static function detectBasePath(): string
    {
        // When installed as package, look for composer.json in parent directories
        $current = getcwd();
        while ($current !== '/') {
            if (file_exists($current . '/composer.json')) {
                $composer = json_decode(file_get_contents($current . '/composer.json'), true);
                // Check if this is an application (project) or package (library)
                if (($composer['type'] ?? 'library') === 'project') {
                    return $current;
                }
            }
            $current = dirname($current);
        }

        // Fallback to current working directory
        return getcwd();
    }
}
