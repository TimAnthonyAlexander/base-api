<?php

namespace BaseApi\Database\Drivers;

use InvalidArgumentException;

class DatabaseDriverFactory
{
    private static array $drivers = [];
    
    /**
     * Create a database driver instance
     */
    public static function create(string $driver): DatabaseDriverInterface
    {
        if (isset(self::$drivers[$driver])) {
            return self::$drivers[$driver];
        }
        
        $instance = match ($driver) {
            'mysql' => new MySqlDriver(),
            'sqlite' => new SqliteDriver(),
            'postgresql', 'pgsql' => new PostgreSqlDriver(),
            default => throw new InvalidArgumentException("Unsupported database driver: {$driver}")
        };
        
        self::$drivers[$driver] = $instance;
        return $instance;
    }
    
    /**
     * Get available drivers
     */
    public static function getAvailableDrivers(): array
    {
        return ['mysql', 'sqlite', 'postgresql'];
    }
    
    /**
     * Check if a driver is supported
     */
    public static function isSupported(string $driver): bool
    {
        return in_array($driver, self::getAvailableDrivers(), true);
    }
    
    /**
     * Clear driver cache (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$drivers = [];
    }
}
