<?php

namespace BaseApi\Container;

/**
 * Container interface for dependency injection.
 * 
 * Provides methods to bind services, resolve dependencies,
 * and check if services are bound in the container.
 */
interface ContainerInterface
{
    /**
     * Bind a service to the container.
     * 
     * @param string $abstract The service identifier (usually class name or interface)
     * @param mixed $concrete The concrete implementation (class name, closure, or instance)
     * @param bool $singleton Whether to treat as singleton (default: false)
     */
    public function bind(string $abstract, mixed $concrete = null, bool $singleton = false): void;

    /**
     * Bind a service as singleton to the container.
     * 
     * @param string $abstract The service identifier
     * @param mixed $concrete The concrete implementation
     */
    public function singleton(string $abstract, mixed $concrete = null): void;

    /**
     * Bind an existing instance to the container.
     * 
     * @param string $abstract The service identifier
     * @param object $instance The instance to bind
     */
    public function instance(string $abstract, object $instance): void;

    /**
     * Resolve a service from the container.
     * 
     * @param string $abstract The service identifier
     * @param array $parameters Additional parameters for construction
     * @return mixed The resolved service
     * @throws ContainerException If service cannot be resolved
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Check if a service is bound in the container.
     * 
     * @param string $abstract The service identifier
     * @return bool True if bound, false otherwise
     */
    public function bound(string $abstract): bool;

    /**
     * Remove a binding from the container.
     * 
     * @param string $abstract The service identifier
     */
    public function forget(string $abstract): void;

    /**
     * Get all bindings.
     * 
     * @return array All registered bindings
     */
    public function getBindings(): array;
}

