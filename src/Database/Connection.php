<?php

namespace BaseApi\Database;

use BaseApi\Database\Drivers\DatabaseDriverFactory;
use BaseApi\Database\Drivers\DatabaseDriverInterface;
use BaseApi\App;

class Connection
{
    private ?\PDO $pdo = null;
    private ?DatabaseDriverInterface $driver = null;

    public function pdo(): \PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }
    
    public function getDriver(): DatabaseDriverInterface
    {
        if ($this->driver === null) {
            $driverName = $_ENV['DB_DRIVER'] ?? 'mysql';
            $this->driver = DatabaseDriverFactory::create($driverName);
        }
        
        return $this->driver;
    }

    private function connect(): void
    {
        $driver = $this->getDriver();
        
        $config = [
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? ($driver->getName() === 'mysql' ? '3306' : null),
            'database' => $_ENV['DB_NAME'] ?? ($_ENV['DB_DATABASE'] ?? 'baseapi'),
            'username' => $_ENV['DB_USER'] ?? ($_ENV['DB_USERNAME'] ?? 'root'),
            'password' => $_ENV['DB_PASSWORD'] ?? ($_ENV['DB_PASS'] ?? ''),
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'persistent' => ($_ENV['DB_PERSISTENT'] ?? 'false') === 'true',
        ];
        
        $this->pdo = $driver->createConnection($config);
    }

    /**
     * Execute a query with profiling support
     */
    public function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        $start = hrtime(true);
        $exception = null;
        
        try {
            $pdo = $this->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
            
        } catch (\Exception $e) {
            $exception = $e;
            throw $e;
            
        } finally {
            // Log query to profiler if enabled
            if (class_exists('BaseApi\App') && method_exists(App::class, 'profiler')) {
                $profiler = App::profiler();
                if ($profiler && $profiler->isEnabled()) {
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
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Execute a query and return the first result
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
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
}
