<?php

namespace BaseApi\Permissions;

use BaseApi\App;

/**
 * Permissions facade for convenient static access.
 */
class Permissions
{
    private static ?PermissionsService $service = null;

    /**
     * Get the permissions service instance.
     */
    public static function service(): PermissionsService
    {
        if (!self::$service instanceof PermissionsService) {
            self::$service = App::container()->make(PermissionsService::class);
        }

        return self::$service;
    }

    /**
     * Check if a user has permission by user ID.
     * For checking current authenticated user, use check() with the user's ID.
     */
    public static function allows(string $userId, string $node): bool
    {
        return self::service()->check($userId, $node);
    }

    /**
     * Check if a specific user has permission.
     */
    public static function check(string $userId, string $node): bool
    {
        return self::service()->check($userId, $node);
    }

    /**
     * Check if a role has permission.
     */
    public static function checkRole(string $role, string $node): bool
    {
        return self::service()->checkRole($role, $node);
    }

    /**
     * Get all permissions for a role.
     */
    public static function getRolePermissions(string $role): array
    {
        return self::service()->getRolePermissions($role);
    }

    /**
     * Trace permission resolution for debugging.
     */
    public static function trace(string $userId, string $node): array
    {
        return self::service()->trace($userId, $node);
    }

    /**
     * Reset the service instance (useful for testing).
     */
    public static function reset(): void
    {
        self::$service = null;
    }
}

