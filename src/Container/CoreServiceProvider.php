<?php

namespace BaseApi\Container;

use BaseApi\Config;
use BaseApi\Logger;
use BaseApi\Router;
use BaseApi\Profiler;
use BaseApi\Database\Connection;
use BaseApi\Database\DB;
use BaseApi\Http\Kernel;
use BaseApi\Http\ControllerInvoker;
use BaseApi\Http\Binding\ControllerBinder;

/**
 * Core service provider for BaseAPI framework services.
 * 
 * Registers all core framework services in the container.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Register Config as singleton
        $container->singleton(Config::class, function (ContainerInterface $c) {
            // Load framework defaults
            $frameworkDefaults = require __DIR__ . '/../../config/defaults.php';

            // Load application configuration
            $appConfig = [];
            $configFile = \BaseApi\App::basePath('config/app.php');
            if (file_exists($configFile)) {
                $appConfig = require $configFile;
            }

            // Merge configurations: framework defaults < app config
            $config = array_replace_recursive($frameworkDefaults, $appConfig);

            return new Config($config, $_ENV);
        });

        // Register Logger as singleton
        $container->singleton(Logger::class);

        // Register Router as singleton
        $container->singleton(Router::class);

        // Register Profiler as singleton
        $container->singleton(Profiler::class);

        // Register Database Connection as singleton
        $container->singleton(Connection::class);

        // Register DB as singleton
        $container->singleton(DB::class, function (ContainerInterface $c) {
            return new DB($c->make(Connection::class));
        });

        // Register HTTP components
        $container->singleton(ControllerBinder::class);
        $container->singleton(ControllerInvoker::class);

        // Register Kernel as singleton
        $container->singleton(Kernel::class, function (ContainerInterface $c) {
            return new Kernel($c->make(Router::class), $c);
        });

        // Register container itself
        $container->instance(ContainerInterface::class, $container);
        $container->instance(Container::class, $container);
    }

    public function boot(ContainerInterface $container): void
    {
        // Configure Kernel with global middleware
        $kernel = $container->make(Kernel::class);
        
        // Register global middleware in order (outer → inner)
        $kernel->addGlobal(\BaseApi\Http\ProfilerMiddleware::class);
        $kernel->addGlobal(\BaseApi\Http\RequestIdMiddleware::class);
        $kernel->addGlobal(\BaseApi\Http\ResponseTimeMiddleware::class);
        $kernel->addGlobal(\BaseApi\Http\CorsMiddleware::class);
        $kernel->addGlobal(\BaseApi\Http\SecurityHeadersMiddleware::class);
        $kernel->addGlobal(\BaseApi\Http\ErrorHandler::class);
        $kernel->addGlobal(\BaseApi\Http\JsonBodyParserMiddleware::class);
        $kernel->addGlobal(\BaseApi\Http\FormBodyParserMiddleware::class);
        $kernel->addGlobal(\BaseApi\Http\SessionStartMiddleware::class);
    }
}
