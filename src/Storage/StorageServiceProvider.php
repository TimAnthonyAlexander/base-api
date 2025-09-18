<?php

namespace BaseApi\Storage;

use BaseApi\Container\ContainerInterface;
use BaseApi\Container\ServiceProvider;
use BaseApi\Config;

/**
 * Storage service provider for dependency injection registration.
 */
class StorageServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Register the storage manager as singleton
        $container->singleton(StorageManager::class, function () use ($container) {
            $config = $container->make(Config::class);
            return new StorageManager($config);
        });

        // Register the storage interface binding to default disk
        $container->bind(StorageInterface::class, function () use ($container) {
            $manager = $container->make(StorageManager::class);
            return $manager->disk();
        });
    }

    public function boot(ContainerInterface $container): void
    {
        // No boot logic needed for storage
    }
}

