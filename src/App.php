<?php

namespace BaseApi;

use BaseApi\Http\Kernel;
use BaseApi\Database\Connection;
use BaseApi\Database\DB;
use BaseApi\Auth\UserProvider;
use BaseApi\Container\Container;
use BaseApi\Container\ContainerInterface;
use BaseApi\Container\CoreServiceProvider;

class App
{
    private static ?Config $config = null;
    private static ?Logger $logger = null;
    private static ?Router $router = null;
    private static ?Kernel $kernel = null;
    private static ?Connection $connection = null;
    private static ?DB $db = null;
    private static ?UserProvider $userProvider = null;
    private static ?Profiler $profiler = null;
    private static bool $booted = false;
    private static ?string $basePath = null;
    private static ?ContainerInterface $container = null;
    private static array $serviceProviders = [];

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

        // Initialize container
        self::$container = new Container();

        // Register core service provider
        self::$serviceProviders[] = new CoreServiceProvider();

        // Load application service providers from config
        $frameworkDefaults = require __DIR__ . '/../config/defaults.php';
        $appConfig = [];
        $configFile = self::$basePath . '/config/app.php';
        if (file_exists($configFile)) {
            $appConfig = require $configFile;
        }
        $config = array_replace_recursive($frameworkDefaults, $appConfig);
        
        // Register application providers from config
        $providers = $config['providers'] ?? [];
        foreach ($providers as $providerClass) {
            if (is_string($providerClass)) {
                self::$serviceProviders[] = new $providerClass();
            } else {
                self::$serviceProviders[] = $providerClass;
            }
        }

        // Register services from all providers
        foreach (self::$serviceProviders as $provider) {
            $provider->register(self::$container);
        }

        // Boot services from all providers
        foreach (self::$serviceProviders as $provider) {
            $provider->boot(self::$container);
        }

        // Initialize legacy static properties for backward compatibility
        self::$config = self::$container->make(Config::class);
        self::$logger = self::$container->make(Logger::class);
        self::$router = self::$container->make(Router::class);
        self::$connection = self::$container->make(Connection::class);
        self::$db = self::$container->make(DB::class);
        self::$profiler = self::$container->make(Profiler::class);
        self::$kernel = self::$container->make(Kernel::class);

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

    public static function profiler(): Profiler
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$profiler;
    }

    public static function container(): ContainerInterface
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$container;
    }

    public static function registerProvider($provider): void
    {
        if (is_string($provider)) {
            $provider = new $provider();
        }

        self::$serviceProviders[] = $provider;

        // If already booted, register and boot the provider immediately
        if (self::$booted && self::$container) {
            $provider->register(self::$container);
            $provider->boot(self::$container);
        }
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
