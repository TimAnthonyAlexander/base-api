<?php

namespace BaseApi\Database;

class Connection
{
    private ?\PDO $pdo = null;

    public function pdo(): \PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    private function connect(): void
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_NAME'] ?? 'baseapi';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        $persistent = ($_ENV['DB_PERSISTENT'] ?? 'false') === 'true';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_PERSISTENT => $persistent,
        ];

        try {
            $this->pdo = new \PDO($dsn, $username, $password, $options);
            
            // Set timezone to UTC
            $this->pdo->exec("SET time_zone = '+00:00'");
            
            // Set names (charset)
            $this->pdo->exec("SET NAMES {$charset}");
            
        } catch (\PDOException $e) {
            throw new DbException("Database connection failed: " . $e->getMessage(), $e);
        }
    }
}
