<?php

namespace BaseApi\Permissions;

use Override;
use Exception;
use BaseApi\Container\ServiceProvider;
use BaseApi\Container\ContainerInterface;
use BaseApi\Auth\UserProvider;
use BaseApi\App;

/**
 * Service provider for the permissions system.
 */
class PermissionsServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(ContainerInterface $container): void
    {
        // Register PermissionsService as singleton
        $container->singleton(PermissionsService::class, function (ContainerInterface $c): PermissionsService {
            $filePath = App::storagePath('permissions/permissions.json');
            $service = new PermissionsService($filePath);
            
            // Set user provider if available
            try {
                $userProvider = $c->make(UserProvider::class);
                $service->setUserProvider($userProvider);
            } catch (Exception) {
                // User provider not bound yet, that's okay
            }
            
            return $service;
        });

        // Register PermissionsMiddleware
        $container->bind(PermissionsMiddleware::class);
    }

    #[Override]
    public function boot(ContainerInterface $container): void
    {
        // Boot method - nothing to do here
    }
}


