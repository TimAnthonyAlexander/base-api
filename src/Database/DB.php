<?php

namespace BaseApi\Database;

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
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new DbException("Query failed: " . $e->getMessage(), $e);
        }
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new DbException("Scalar query failed: " . $e->getMessage(), $e);
        }
    }

    public function exec(string $sql, array $bindings = []): int
    {
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new DbException("Execute failed: " . $e->getMessage(), $e);
        }
    }
}
