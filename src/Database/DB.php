<?php

namespace BaseApi\Database;

use BaseApi\App;

class DB
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function qb(): QueryBuilder
    {
        return new QueryBuilder($this->connection);
    }

    public function pdo(): \PDO
    {
        return $this->connection->pdo();
    }
    
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function raw(string $sql, array $bindings = []): array
    {
        $start = hrtime(true);
        $exception = null;
        
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $result = $stmt->fetchAll();
            
            // Log to profiler if available and enabled
            $this->logQueryToProfiler($sql, $bindings, $start, $exception);
            
            return $result;
        } catch (\PDOException $e) {
            $exception = $e;
            $this->logQueryToProfiler($sql, $bindings, $start, $exception);
            throw new DbException("Query failed: " . $e->getMessage(), $e);
        }
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        $start = hrtime(true);
        $exception = null;
        
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $result = $stmt->fetchColumn();
            
            // Log to profiler if available and enabled
            $this->logQueryToProfiler($sql, $bindings, $start, $exception);
            
            return $result;
        } catch (\PDOException $e) {
            $exception = $e;
            $this->logQueryToProfiler($sql, $bindings, $start, $exception);
            throw new DbException("Scalar query failed: " . $e->getMessage(), $e);
        }
    }

    public function exec(string $sql, array $bindings = []): int
    {
        $start = hrtime(true);
        $exception = null;
        
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $result = $stmt->rowCount();
            
            // Log to profiler if available and enabled
            $this->logQueryToProfiler($sql, $bindings, $start, $exception);
            
            return $result;
        } catch (\PDOException $e) {
            $exception = $e;
            $this->logQueryToProfiler($sql, $bindings, $start, $exception);
            throw new DbException("Execute failed: " . $e->getMessage(), $e);
        }
    }

    /**
     * Log query to profiler if available and enabled
     */
    private function logQueryToProfiler(string $sql, array $bindings, int $startTime, ?\Throwable $exception = null): void
    {
        // Only log if App class exists and profiler is available
        if (!class_exists('BaseApi\App')) {
            return;
        }

        try {
            $profiler = App::profiler();
            if ($profiler && $profiler->isEnabled()) {
                $duration = (hrtime(true) - $startTime) / 1_000_000; // Convert to milliseconds
                $profiler->logQuery($sql, $bindings, $duration, $exception);
            }
        } catch (\Throwable $e) {
            // Silently ignore profiler errors to avoid disrupting queries
        }
    }
}
