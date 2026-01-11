<?php

namespace BaseApi\Database;

use PDO;
use PDOStatement;
use Exception;
use BaseApi\Database\Drivers\DatabaseDriverFactory;
use BaseApi\Database\Drivers\DatabaseDriverInterface;
use BaseApi\App;

class Connection
{
    private ?PDO $pdo = null;

    private ?DatabaseDriverInterface $driver = null;

    public function pdo(): PDO
    {
        if (!$this->pdo instanceof PDO) {
            $this->connect();
        }

        return $this->pdo;
    }

    public function getDriver(): DatabaseDriverInterface
    {
        if (!$this->driver instanceof DatabaseDriverInterface) {
            $driverName = App::config('database.driver', 'mysql');
            $this->driver = DatabaseDriverFactory::create($driverName);
        }

        return $this->driver;
    }

    private function connect(): void
    {
        $driver = $this->getDriver();

        $defaultPort = $driver->getName() === 'mysql' ? 3306 : null;
        
        $config = [
            'host' => App::config('database.host', '127.0.0.1'),
            'port' => App::config('database.port', $defaultPort),
            'database' => App::config('database.name', 'baseapi'),
            'username' => App::config('database.user', 'root'),
            'password' => App::config('database.password', ''),
            'charset' => App::config('database.charset', 'utf8mb4'),
            'persistent' => App::config('database.persistent', false),
            'timeout' => App::config('database.timeout', $_ENV['DB_TIMEOUT'] ?? null),
        ];

        $this->pdo = $driver->createConnection($config);
    }

    /**
     * Execute a query with profiling support
     */
    public function executeQuery(string $sql, array $params = []): PDOStatement
    {
        $start = hrtime(true);
        $exception = null;

        try {
            $pdo = $this->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt;

        } catch (Exception $e) {
            $exception = $e;
            throw $e;

        } finally {
            // Log query to profiler if enabled
            if (class_exists(App::class) && method_exists(App::class, 'profiler')) {
                $profiler = App::profiler();
                if ($profiler->isEnabled()) {
                    $duration = (hrtime(true) - $start) / 1_000_000; // Convert to milliseconds
                    $profiler->logQuery($sql, $params, $duration, $exception);
                }
            }
        }
    }

    /**
     * Execute a query and return all results
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a query and return the first result
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    /**
     * Execute a query and return the number of affected rows
     */
    public function exec(string $sql, array $params = []): int
    {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Create a new query builder instance
     */
    public function qb(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Begin a database transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    /**
     * Commit the current transaction
     */
    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    /**
     * Roll back the current transaction
     */
    public function rollback(): bool
    {
        return $this->pdo()->rollBack();
    }
}
