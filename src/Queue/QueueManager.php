<?php

namespace BaseApi\Queue;

use BaseApi\Queue\Drivers\DatabaseQueueDriver;
use BaseApi\Queue\Drivers\SyncQueueDriver;
use BaseApi\Queue\Exceptions\QueueException;
use BaseApi\App;

/**
 * Queue manager that provides access to different queue drivers.
 */
class QueueManager
{
    private array $drivers = [];

    public function __construct(private readonly string $defaultDriver = 'database')
    {
    }

    /**
     * Get a queue driver instance.
     */
    public function driver(?string $name = null): QueueInterface
    {
        $name ??= $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Push a job onto the default queue.
     */
    public function push(JobInterface $job, string $queue = 'default', int $delay = 0): string
    {
        return $this->driver()->push($job, $queue, $delay);
    }

    /**
     * Pop a job from the default queue.
     */
    public function pop(string $queue = 'default'): ?QueueJob
    {
        return $this->driver()->pop($queue);
    }

    /**
     * Get the size of a queue.
     */
    public function size(string $queue = 'default'): int
    {
        return $this->driver()->size($queue);
    }

    /**
     * Create a queue driver instance.
     *
     * @throws QueueException
     */
    private function createDriver(string $name): QueueInterface
    {
        $config = App::config('queue.drivers.' . $name);

        if (!$config) {
            throw new QueueException(sprintf('Queue driver [%s] not configured', $name));
        }

        $driver = $config['driver'] ?? $name;

        return match ($driver) {
            'database' => new DatabaseQueueDriver(App::db()->getConnection()),
            'sync' => new SyncQueueDriver(),
            default => throw new QueueException('Unsupported queue driver: ' . $driver),
        };
    }
}
