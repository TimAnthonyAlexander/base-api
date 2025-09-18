<?php

namespace BaseApi\Container;

use Exception;

/**
 * Exception thrown when container operations fail.
 */
class ContainerException extends Exception
{
    public static function serviceNotFound(string $service): self
    {
        return new self("Service '{$service}' not found in container and cannot be auto-wired.");
    }

    public static function circularDependency(string $service, array $stack): self
    {
        $stackStr = implode(' -> ', $stack);
        return new self("Circular dependency detected for '{$service}': {$stackStr}");
    }

    public static function cannotInstantiate(string $service, string $reason): self
    {
        return new self("Cannot instantiate '{$service}': {$reason}");
    }

    public static function invalidBinding(string $service, string $reason): self
    {
        return new self("Invalid binding for '{$service}': {$reason}");
    }
}

