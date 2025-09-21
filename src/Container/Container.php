<?php

namespace BaseApi\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionNamedType;
use Closure;

/**
 * Dependency injection container with auto-wiring capabilities.
 * 
 * Supports:
 * - Service binding (transient and singleton)
 * - Instance binding
 * - Auto-wiring based on constructor type hints
 * - Circular dependency detection
 * - Parameter injection
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, array{concrete: mixed, singleton: bool, instance?: object}>
     */
    private array $bindings = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * @var array<string, bool>
     */
    private array $buildStack = [];

    public function bind(string $abstract, mixed $concrete = null, bool $singleton = false): void
    {
        // If no concrete provided, use the abstract as concrete
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton,
        ];

        // Remove any existing instance if rebinding
        unset($this->instances[$abstract]);
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->bindings[$abstract] = [
            'concrete' => get_class($instance),
            'singleton' => true,
            'instance' => $instance,
        ];
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        // Check for circular dependency
        if (isset($this->buildStack[$abstract])) {
            $stack = array_keys($this->buildStack);
            $stack[] = $abstract;
            throw ContainerException::circularDependency($abstract, $stack);
        }

        // If we have a singleton instance, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Mark as building
        $this->buildStack[$abstract] = true;

        try {
            $instance = $this->build($abstract, $parameters);

            // If singleton, store the instance
            if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['singleton']) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        } finally {
            // Remove from build stack
            unset($this->buildStack[$abstract]);
        }
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    public function forget(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Build an instance of the given service.
     */
    private function build(string $abstract, array $parameters = []): mixed
    {
        $concrete = $this->getConcrete($abstract);

        // If concrete is a closure, call it
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        // If concrete is not a string, return it as-is (for primitive values)
        if (!is_string($concrete)) {
            return $concrete;
        }

        // If concrete is a string but not a class, return it as-is (for string values)
        if (!class_exists($concrete) && !interface_exists($concrete)) {
            return $concrete;
        }

        return $this->buildClass($concrete, $parameters);
    }

    /**
     * Get the concrete implementation for an abstract.
     */
    private function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        // If not bound, try to auto-wire the abstract itself
        return $abstract;
    }

    /**
     * Build a class instance with dependency injection.
     */
    private function buildClass(string $className, array $parameters = []): object
    {
        try {
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw ContainerException::cannotInstantiate($className, "Class does not exist: {$e->getMessage()}");
        }

        // Check if class is instantiable
        if (!$reflection->isInstantiable()) {
            throw ContainerException::cannotInstantiate($className, 'Class is not instantiable (abstract or interface)');
        }

        $constructor = $reflection->getConstructor();

        // If no constructor, just create instance
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        // Resolve constructor dependencies
        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve method dependencies.
     * 
     * @param ReflectionParameter[] $parameters
     * @param array $primitives
     * @return array
     */
    private function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // If we have a primitive value for this parameter, use it
            if (array_key_exists($name, $primitives)) {
                $dependencies[] = $primitives[$name];
                continue;
            }

            // Try to resolve by type hint
            $type = $parameter->getType();
            
            if ($type === null) {
                // No type hint, check if parameter has default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw ContainerException::cannotInstantiate(
                    $parameter->getDeclaringClass()->getName(),
                    "Cannot resolve parameter '{$name}' - no type hint and no default value"
                );
            }

            // Handle union types (PHP 8.0+)
            if ($type instanceof \ReflectionUnionType) {
                throw ContainerException::cannotInstantiate(
                    $parameter->getDeclaringClass()->getName(),
                    "Cannot resolve parameter '{$name}' - union types not supported"
                );
            }

            if (!$type instanceof ReflectionNamedType) {
                throw ContainerException::cannotInstantiate(
                    $parameter->getDeclaringClass()->getName(),
                    "Cannot resolve parameter '{$name}' - only named types are supported"
                );
            }

            $typeName = $type->getName();

            // Handle built-in types
            if ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw ContainerException::cannotInstantiate(
                    $parameter->getDeclaringClass()->getName(),
                    "Cannot resolve built-in type '{$typeName}' for parameter '{$name}'"
                );
            }

            // Try to resolve the class
            try {
                $dependencies[] = $this->make($typeName);
            } catch (ContainerException $e) {
                // If parameter is optional, use default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                // If parameter allows null, pass null
                if ($parameter->allowsNull()) {
                    $dependencies[] = null;
                    continue;
                }

                throw $e;
            }
        }

        return $dependencies;
    }

    /**
     * Call a method with dependency injection.
     * 
     * @param object|string $target The object instance or class name
     * @param string $method The method name
     * @param array $parameters Additional parameters
     * @return mixed The method result
     */
    public function call(object|string $target, string $method, array $parameters = []): mixed
    {
        if (is_string($target)) {
            $target = $this->make($target);
        }

        try {
            $reflection = new ReflectionClass($target);
            $method = $reflection->getMethod($method);
        } catch (ReflectionException $e) {
            throw ContainerException::cannotInstantiate(
                get_class($target),
                "Method '{$method}' does not exist: {$e->getMessage()}"
            );
        }

        $dependencies = $this->resolveDependencies($method->getParameters(), $parameters);

        return $method->invokeArgs($target, $dependencies);
    }
}
