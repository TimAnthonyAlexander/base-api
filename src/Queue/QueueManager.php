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
    private string $defaultDriver;

    public function __construct(string $defaultDriver = 'database')
    {
        $this->defaultDriver = $defaultDriver;
    }

    /**
     * Get a queue driver instance.
     *
     * @param string|null $name
     * @return QueueInterface
     */
    public function driver(?string $name = null): QueueInterface
    {
        $name = $name ?? $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Push a job onto the default queue.
     *
     * @param JobInterface $job
     * @param string $queue
     * @param int $delay
     * @return string
     */
    public function push(JobInterface $job, string $queue = 'default', int $delay = 0): string
    {
        return $this->driver()->push($job, $queue, $delay);
    }

    /**
     * Pop a job from the default queue.
     *
     * @param string $queue
     * @return QueueJob|null
     */
    public function pop(string $queue = 'default'): ?QueueJob
    {
        return $this->driver()->pop($queue);
    }

    /**
     * Get the size of a queue.
     *
     * @param string $queue
     * @return int
     */
    public function size(string $queue = 'default'): int
    {
        return $this->driver()->size($queue);
    }

    /**
     * Create a queue driver instance.
     *
     * @param string $name
     * @return QueueInterface
     * @throws QueueException
     */
    private function createDriver(string $name): QueueInterface
    {
        $config = App::config("queue.drivers.{$name}");

        if (!$config) {
            throw new QueueException("Queue driver [{$name}] not configured");
        }

        $driver = $config['driver'] ?? $name;

        switch ($driver) {
            case 'database':
                return new DatabaseQueueDriver(App::db()->getConnection());

            case 'sync':
                return new SyncQueueDriver();

            default:
                throw new QueueException("Unsupported queue driver: {$driver}");
        }
    }
}
