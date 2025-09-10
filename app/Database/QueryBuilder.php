<?php

namespace BaseApi\Database;

class QueryBuilder
{
    private Connection $connection;
    private ?string $table = null;
    private array $columns = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private array $orders = [];
    private ?int $limitCount = null;
    private ?int $offsetCount = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function table(string $name): self
    {
        $this->table = $this->sanitizeColumnName($name);
        return $this;
    }

    public function select(string|array $columns = '*'): self
    {
        $this->type = 'select';
        
        if (is_string($columns)) {
            $this->columns = $columns === '*' ? ['*'] : [$this->sanitizeColumnName($columns)];
        } else {
            $this->columns = array_map([$this, 'sanitizeColumnName'], $columns);
        }
        
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $allowedOperators = ['=', '!=', '<', '<=', '>', '>=', 'LIKE', 'IN'];
        
        if (!in_array(strtoupper($operator), array_map('strtoupper', $allowedOperators))) {
            throw new DbException("Invalid operator: {$operator}");
        }

        $column = $this->sanitizeColumnName($column);
        $placeholder = $this->addBinding($value);
        
        $this->wheres[] = "{$column} {$operator} {$placeholder}";
        
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            // Handle empty array - add impossible condition
            $this->wheres[] = '1 = 0';
            return $this;
        }

        $column = $this->sanitizeColumnName($column);
        $placeholders = [];
        
        foreach ($values as $value) {
            $placeholders[] = $this->addBinding($value);
        }
        
        $this->wheres[] = "{$column} IN (" . implode(', ', $placeholders) . ")";
        
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $column = $this->sanitizeColumnName($column);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        
        $this->orders[] = "{$column} {$direction}";
        
        return $this;
    }

    public function limit(int $count): self
    {
        $this->limitCount = max(0, $count);
        return $this;
    }

    public function offset(int $count): self
    {
        $this->offsetCount = max(0, $count);
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelectSql();
        return $this->execute($sql);
    }

    public function first(): ?array
    {
        $originalLimit = $this->limitCount;
        $this->limit(1);
        
        $results = $this->get();
        
        $this->limitCount = $originalLimit;
        
        return $results[0] ?? null;
    }

    public function insert(array $data): bool
    {
        if (empty($data)) {
            throw new DbException("Insert data cannot be empty");
        }

        $this->validateTable();
        
        $columns = array_map([$this, 'sanitizeColumnName'], array_keys($data));
        $placeholders = [];
        
        foreach ($data as $value) {
            $placeholders[] = $this->addBinding($value);
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->execute($sql);
        return true;
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $this->validateTable();
        
        $sets = [];
        foreach ($data as $column => $value) {
            $column = $this->sanitizeColumnName($column);
            $placeholder = $this->addBinding($value);
            $sets[] = "{$column} = {$placeholder}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        
        return $this->executeUpdate($sql);
    }

    public function delete(): int
    {
        $this->validateTable();
        
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        
        return $this->executeUpdate($sql);
    }

    public function toSql(): array
    {
        return [
            'sql' => $this->buildSelectSql(),
            'bindings' => $this->bindings
        ];
    }

    /**
     * Paginate results and optionally include total count
     */
    public function paginate(int $page, int $perPage, bool $withTotal = false): PaginatedResult
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $total = null;
        if ($withTotal) {
            // Clone the builder state but remove order/limit for count
            $countBuilder = clone $this;
            $countBuilder->orders = [];
            $countBuilder->limitCount = null;
            $countBuilder->offsetCount = null;
            $countBuilder->columns = ['COUNT(*) as count'];
            
            $countResult = $countBuilder->get();
            $total = (int) $countResult[0]['count'];
        }

        // Apply pagination to current builder
        $this->limit($perPage)->offset($offset);
        $data = $this->get();

        return new PaginatedResult($data, $page, $perPage, $total);
    }

    /**
     * Parse sort string like "name,-createdAt" into orderBy calls
     */
    public function applySortString(string $sort): self
    {
        if (empty($sort)) {
            return $this;
        }

        $sorts = explode(',', $sort);
        foreach ($sorts as $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }

            $direction = 'asc';
            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = substr($field, 1);
            }

            // Convert camelCase to snake_case
            $column = $this->camelToSnake($field);
            $this->orderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Apply filters as exact matches
     */
    public function applyFilters(array $filters): self
    {
        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Convert camelCase to snake_case
            $column = $this->camelToSnake($field);
            $this->where($column, '=', $value);
        }

        return $this;
    }

    private function buildSelectSql(): string
    {
        $this->validateTable();
        
        $sql = 'SELECT ' . implode(', ', $this->columns) . " FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        
        if ($this->limitCount !== null) {
            $sql .= " LIMIT {$this->limitCount}";
        }
        
        if ($this->offsetCount !== null) {
            $sql .= " OFFSET {$this->offsetCount}";
        }
        
        return $sql;
    }

    private function execute(string $sql): array
    {
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($this->bindings);
            
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new DbException("Query execution failed: " . $e->getMessage(), $e);
        }
    }

    private function executeUpdate(string $sql): int
    {
        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($this->bindings);
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new DbException("Update execution failed: " . $e->getMessage(), $e);
        }
    }

    private function addBinding(mixed $value): string
    {
        $this->bindings[] = $value;
        return '?';
    }

    private function sanitizeColumnName(string $name): string
    {
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $name)) {
            throw new DbException("Invalid column name: {$name}");
        }
        
        return $name;
    }

    private function validateTable(): void
    {
        if ($this->table === null) {
            throw new DbException("Table not specified");
        }
    }

    /**
     * Convert camelCase to snake_case for database column names
     */
    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }
}
