<?php

namespace BaseApi\Database;

use BaseApi\Database\Drivers\DatabaseDriverFactory;
use BaseApi\Database\Drivers\DatabaseDriverInterface;

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
}
