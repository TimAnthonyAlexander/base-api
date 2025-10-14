<?php

namespace BaseApi\Container;

/**
 * Base service provider class.
 * 
 * Provides default implementations for service provider methods.
 */
abstract class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Register services in the container.
     * 
     * @param ContainerInterface $container The container instance
     */
    abstract public function register(ContainerInterface $container): void;

    /**
     * Boot services after all providers have been registered.
     * 
     * Default implementation does nothing. Override if needed.
     * 
     * @param ContainerInterface $container The container instance
     */
    public function boot(ContainerInterface $container): void
    {
        // Default implementation does nothing
    }
}

