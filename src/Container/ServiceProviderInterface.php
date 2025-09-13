<?php

namespace BaseApi\Container;

/**
 * Interface for service providers.
 * 
 * Service providers are responsible for binding services into the container.
 * They provide a clean way to organize service registration.
 */
interface ServiceProviderInterface
{
    /**
     * Register services in the container.
     * 
     * @param ContainerInterface $container The container instance
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot services after all providers have been registered.
     * 
     * This method is called after all service providers have been registered,
     * allowing for service configuration that depends on other services.
     * 
     * @param ContainerInterface $container The container instance
     */
    public function boot(ContainerInterface $container): void;
}
