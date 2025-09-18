<?php

namespace BaseApi\Storage;

use BaseApi\Config;
use BaseApi\Storage\Drivers\LocalDriver;
use InvalidArgumentException;

/**
 * Storage manager handles different storage drivers and configuration.
 */
class StorageManager
{
    private Config $config;
    private array $drivers = [];
    private array $customDrivers = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get a storage driver instance.
     * 
     * @param string|null $name Driver name (null for default)
     * @return StorageInterface
     */
    public function disk(?string $name = null): StorageInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Get the default storage driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('filesystems.default', 'local');
    }

    /**
     * Set the default storage driver.
     */
    public function setDefaultDriver(string $name): void
    {
        // This would need to update the config, but for now just keep it simple
        // In a real implementation, you might want to store this in a property
    }

    /**
     * Register a custom driver creator.
     * 
     * @param string $driver Driver name
     * @param callable $callback Driver creator callback
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customDrivers[$driver] = $callback;
    }

    /**
     * Get all configured disk names.
     * 
     * @return array
     */
    public function getDisks(): array
    {
        $disks = $this->config->get('filesystems.disks', []);
        return array_keys($disks);
    }

    /**
     * Resolve a storage driver by name.
     * 
     * @param string $name Driver name
     * @return StorageInterface
     * @throws InvalidArgumentException If driver not found or invalid config
     */
    protected function resolve(string $name): StorageInterface
    {
        $config = $this->getConfig($name);

        if (isset($this->customDrivers[$config['driver']])) {
            return $this->customDrivers[$config['driver']]($config);
        }

        return $this->createDriver($config);
    }

    /**
     * Get configuration for a disk.
     * 
     * @param string $name Disk name
     * @return array
     * @throws InvalidArgumentException If disk not configured
     */
    protected function getConfig(string $name): array
    {
        $disks = $this->config->get('filesystems.disks', []);
        
        if (!isset($disks[$name])) {
            throw new InvalidArgumentException("Storage disk [{$name}] is not configured.");
        }

        return array_merge(['name' => $name], $disks[$name]);
    }

    /**
     * Create a storage driver based on configuration.
     * 
     * @param array $config Driver configuration
     * @return StorageInterface
     * @throws InvalidArgumentException If driver type not supported
     */
    protected function createDriver(array $config): StorageInterface
    {
        $driver = $config['driver'] ?? null;

        return match ($driver) {
            'local' => $this->createLocalDriver($config),
            default => throw new InvalidArgumentException("Storage driver [{$driver}] is not supported.")
        };
    }

    /**
     * Create a local filesystem driver.
     * 
     * @param array $config Driver configuration
     * @return LocalDriver
     */
    protected function createLocalDriver(array $config): LocalDriver
    {
        return new LocalDriver(
            root: $config['root'] ?? storage_path('app'),
            url: $config['url'] ?? null,
            permissions: $config['permissions'] ?? []
        );
    }
}
