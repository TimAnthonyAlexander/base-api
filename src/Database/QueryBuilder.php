<?php

namespace BaseApi\Database;

use PDOException;
use Throwable;
use BaseApi\App;

class QueryBuilder
{
    private ?string $table = null;

    private array $columns = ['*'];

    private array $wheres = [];

    private array $bindings = [];

    private array $orders = [];

    private ?int $limitCount = null;

    private ?int $offsetCount = null;

    private array $joins = [];

    private bool $forUpdate = false;

    public function __construct(private Connection $connection)
    {
    }

    public function table(string $name): self
    {
        $this->table = $this->sanitizeTableName($name);
        // Reset query state when setting new table
        $this->columns = ['*'];
        $this->wheres = [];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;
        $this->joins = [];
        $this->bindings = [];
        $this->forUpdate = false;
        return $this;
    }

    public function select(string|array $columns = '*'): self
    {
        if (is_string($columns)) {
            $this->columns = $columns === '*' ? ['*'] : [$this->sanitizeColumnName($columns)];
        } else {
            $this->columns = array_map([$this, 'sanitizeColumnName'], $columns);
        }

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $op = strtoupper(trim($operator));
        $allowedOperators = ['=', '!=', '<', '<=', '>', '>=', 'LIKE', 'IN'];

        if (!in_array($op, $allowedOperators, true)) {
            throw new DbException('Invalid operator: ' . $operator);
        }

        $column = $this->sanitizeColumnName($column);

        if ($op === 'IN') {
            if (!is_array($value) || $value === []) {
                throw new DbException('IN requires a non-empty array');
            }

            $placeholders = implode(', ', array_map(fn($v): string => $this->addBinding($v), $value));
            $this->wheres[] = sprintf('%s IN (%s)', $column, $placeholders);
            return $this;
        }

        $placeholder = $this->addBinding($value);
        $this->wheres[] = sprintf('%s %s %s', $column, $op, $placeholder);

        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $op = strtoupper(trim($operator));
        $allowedOperators = ['=', '!=', '<', '<=', '>', '>=', 'LIKE', 'IN'];

        if (!in_array($op, $allowedOperators, true)) {
            throw new DbException('Invalid operator: ' . $operator);
        }

        $column = $this->sanitizeColumnName($column);
        $connector = $this->wheres === [] ? '' : 'OR ';

        if ($op === 'IN') {
            if (!is_array($value) || $value === []) {
                throw new DbException('IN requires a non-empty array');
            }

            $placeholders = implode(', ', array_map(fn($v): string => $this->addBinding($v), $value));
            $this->wheres[] = $connector . sprintf('%s IN (%s)', $column, $placeholders);
            return $this;
        }

        $placeholder = $this->addBinding($value);
        $this->wheres[] = $connector . sprintf('%s %s %s', $column, $op, $placeholder);

        return $this;
    }

    public function whereNull(string $column): self
    {
        $column = $this->sanitizeColumnName($column);
        $this->wheres[] = $column . ' IS NULL';
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $column = $this->sanitizeColumnName($column);
        $this->wheres[] = $column . ' IS NOT NULL';
        return $this;
    }

    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $column = $this->sanitizeColumnName($column);
        $minPlaceholder = $this->addBinding($min);
        $maxPlaceholder = $this->addBinding($max);
        $this->wheres[] = sprintf('%s BETWEEN %s AND %s', $column, $minPlaceholder, $maxPlaceholder);
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        if ($values === []) {
            throw new DbException('NOT IN requires a non-empty array');
        }

        $column = $this->sanitizeColumnName($column);
        $placeholders = [];

        foreach ($values as $value) {
            $placeholders[] = $this->addBinding($value);
        }

        $this->wheres[] = $column . ' NOT IN (' . implode(', ', $placeholders) . ")";

        return $this;
    }

    public function orWhereNull(string $column): self
    {
        $column = $this->sanitizeColumnName($column);
        $connector = $this->wheres === [] ? '' : 'OR ';
        $this->wheres[] = $connector . ($column . ' IS NULL');
        return $this;
    }

    public function orWhereNotNull(string $column): self
    {
        $column = $this->sanitizeColumnName($column);
        $connector = $this->wheres === [] ? '' : 'OR ';
        $this->wheres[] = $connector . ($column . ' IS NOT NULL');
        return $this;
    }

    public function orWhereBetween(string $column, mixed $min, mixed $max): self
    {
        $column = $this->sanitizeColumnName($column);
        $minPlaceholder = $this->addBinding($min);
        $maxPlaceholder = $this->addBinding($max);
        $connector = $this->wheres === [] ? '' : 'OR ';
        $this->wheres[] = $connector . sprintf('%s BETWEEN %s AND %s', $column, $minPlaceholder, $maxPlaceholder);
        return $this;
    }

    public function orWhereNotIn(string $column, array $values): self
    {
        if ($values === []) {
            throw new DbException('NOT IN requires a non-empty array');
        }

        $column = $this->sanitizeColumnName($column);
        $placeholders = [];

        foreach ($values as $value) {
            $placeholders[] = $this->addBinding($value);
        }

        $connector = $this->wheres === [] ? '' : 'OR ';
        $this->wheres[] = $connector . ($column . ' NOT IN (') . implode(', ', $placeholders) . ")";

        return $this;
    }

    /**
     * Add a grouped WHERE clause: WHERE (callback conditions)
     */
    public function whereGroup(callable $callback): self
    {
        $subBuilder = new self($this->connection);
        $subBuilder->table = $this->table;
        $callback($subBuilder);

        if ($subBuilder->wheres !== []) {
            $expr = $subBuilder->buildWhereClause();
            $this->wheres[] = sprintf('(%s)', $expr);
            foreach ($subBuilder->bindings as $binding) {
                $this->bindings[] = $binding;
            }
        }

        return $this;
    }

    /**
     * Add a grouped OR WHERE clause: OR (callback conditions)
     */
    public function orWhereGroup(callable $callback): self
    {
        $subBuilder = new self($this->connection);
        $subBuilder->table = $this->table;
        $callback($subBuilder);

        if ($subBuilder->wheres !== []) {
            $expr = $subBuilder->buildWhereClause();
            $connector = $this->wheres === [] ? '' : 'OR ';
            $this->wheres[] = $connector . sprintf('(%s)', $expr);
            foreach ($subBuilder->bindings as $binding) {
                $this->bindings[] = $binding;
            }
        }

        return $this;
    }

    public function whereConditions(array $conditions): self
    {
        foreach ($conditions as $condition) {
            $column = $condition['column'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if ($column === null || $value === null) {
                throw new DbException("Invalid where condition: " . json_encode($condition));
            }

            $this->where($column, $operator, $value);
        }

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            // Handle empty array - add impossible condition
            $this->wheres[] = '1 = 0';
            return $this;
        }

        $column = $this->sanitizeColumnName($column);
        $placeholders = [];

        foreach ($values as $value) {
            $placeholders[] = $this->addBinding($value);
        }

        $this->wheres[] = $column . ' IN (' . implode(', ', $placeholders) . ")";

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $column = $this->sanitizeColumnName($column);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $this->orders[] = sprintf('%s %s', $column, $direction);

        return $this;
    }

    public function join(string $table, string $firstColumn, string $operator, string $secondColumn, string $type = 'INNER'): self
    {
        $table = $this->sanitizeTableName($table);
        $type = strtoupper(trim($type));

        $allowedTypes = ['INNER', 'LEFT', 'RIGHT', 'CROSS'];
        if (!in_array($type, $allowedTypes)) {
            throw new DbException('Invalid join type: ' . $type);
        }

        if ($type === 'CROSS') {
            $this->joins[] = 'CROSS JOIN ' . $table;
            return $this;
        }

        $firstColumn = $this->sanitizeColumnName($firstColumn);
        $secondColumn = $this->sanitizeColumnName($secondColumn);
        $operator = strtoupper(trim($operator));

        $allowedOperators = ['=', '!=', '<>', '<', '<=', '>', '>='];
        if (!in_array($operator, $allowedOperators, true)) {
            throw new DbException('Invalid join operator: ' . $operator);
        }

        $this->joins[] = sprintf('%s JOIN %s ON %s %s %s', $type, $table, $firstColumn, $operator, $secondColumn);

        return $this;
    }

    public function leftJoin(string $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        return $this->join($table, $firstColumn, $operator, $secondColumn, 'LEFT');
    }

    public function rightJoin(string $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        return $this->join($table, $firstColumn, $operator, $secondColumn, 'RIGHT');
    }

    public function crossJoin(string $table): self
    {
        return $this->join($table, '', '=', '', 'CROSS');
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

    public function lockForUpdate(): self
    {
        $this->forUpdate = true;
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
        if ($data === []) {
            throw new DbException("Insert data cannot be empty");
        }

        $this->validateTable();

        $columns = array_map([$this, 'sanitizeColumnName'], array_keys($data));
        $placeholders = [];

        foreach ($data as $value) {
            $placeholders[] = $this->addBinding($value);
        }

        $sql = sprintf('INSERT INTO %s (', $this->table) . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->execute($sql);
        return true;
    }

    public function update(array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $this->validateTable();

        $sets = [];
        foreach ($data as $column => $value) {
            $column = $this->sanitizeColumnName($column);
            $placeholder = $this->addBinding($value);
            $sets[] = sprintf('%s = %s', $column, $placeholder);
        }

        $sql = sprintf('UPDATE %s SET ', $this->table) . implode(', ', $sets);

        if ($this->wheres !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        return $this->executeUpdate($sql);
    }

    public function delete(): int
    {
        $this->validateTable();

        $sql = 'DELETE FROM ' . $this->table;

        if ($this->wheres !== []) {
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
     * Count records
     */
    public function count(string $column = '*'): int
    {
        $column = $column === '*' ? '*' : $this->sanitizeColumnName($column);

        // Save original state
        $origCols = $this->columns;
        $origOrders = $this->orders;
        $origLimit = $this->limitCount;
        $origOffset = $this->offsetCount;

        // Apply changes for aggregation
        $this->columns = [sprintf('COUNT(%s) as count', $column)];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;

        $result = $this->first();

        // Restore original state
        $this->columns = $origCols;
        $this->orders = $origOrders;
        $this->limitCount = $origLimit;
        $this->offsetCount = $origOffset;

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Count distinct records (useful for joins that might produce duplicates)
     */
    public function countDistinct(string $column): int
    {
        $column = $this->sanitizeColumnName($column);

        // Save original state
        $origCols = $this->columns;
        $origOrders = $this->orders;
        $origLimit = $this->limitCount;
        $origOffset = $this->offsetCount;

        // Apply changes for aggregation
        $this->columns = [sprintf('COUNT(DISTINCT %s) as count', $column)];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;

        $result = $this->first();

        // Restore original state
        $this->columns = $origCols;
        $this->orders = $origOrders;
        $this->limitCount = $origLimit;
        $this->offsetCount = $origOffset;

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Sum values
     */
    public function sum(string $column): float
    {
        $column = $this->sanitizeColumnName($column);

        // Save original state
        $origCols = $this->columns;
        $origOrders = $this->orders;
        $origLimit = $this->limitCount;
        $origOffset = $this->offsetCount;

        // Apply changes for aggregation
        $this->columns = [sprintf('SUM(%s) as sum', $column)];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;

        $result = $this->first();

        // Restore original state
        $this->columns = $origCols;
        $this->orders = $origOrders;
        $this->limitCount = $origLimit;
        $this->offsetCount = $origOffset;

        return (float) ($result['sum'] ?? 0.0);
    }

    /**
     * Average values
     */
    public function avg(string $column): float
    {
        $column = $this->sanitizeColumnName($column);

        // Save original state
        $origCols = $this->columns;
        $origOrders = $this->orders;
        $origLimit = $this->limitCount;
        $origOffset = $this->offsetCount;

        // Apply changes for aggregation
        $this->columns = [sprintf('AVG(%s) as avg', $column)];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;

        $result = $this->first();

        // Restore original state
        $this->columns = $origCols;
        $this->orders = $origOrders;
        $this->limitCount = $origLimit;
        $this->offsetCount = $origOffset;

        return (float) ($result['avg'] ?? 0.0);
    }

    /**
     * Minimum value
     */
    public function min(string $column): mixed
    {
        $column = $this->sanitizeColumnName($column);

        // Save original state
        $origCols = $this->columns;
        $origOrders = $this->orders;
        $origLimit = $this->limitCount;
        $origOffset = $this->offsetCount;

        // Apply changes for aggregation
        $this->columns = [sprintf('MIN(%s) as min', $column)];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;

        $result = $this->first();

        // Restore original state
        $this->columns = $origCols;
        $this->orders = $origOrders;
        $this->limitCount = $origLimit;
        $this->offsetCount = $origOffset;

        return $result['min'] ?? null;
    }

    /**
     * Maximum value
     */
    public function max(string $column): mixed
    {
        $column = $this->sanitizeColumnName($column);

        // Save original state
        $origCols = $this->columns;
        $origOrders = $this->orders;
        $origLimit = $this->limitCount;
        $origOffset = $this->offsetCount;

        // Apply changes for aggregation
        $this->columns = [sprintf('MAX(%s) as max', $column)];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;

        $result = $this->first();

        // Restore original state
        $this->columns = $origCols;
        $this->orders = $origOrders;
        $this->limitCount = $origLimit;
        $this->offsetCount = $origOffset;

        return $result['max'] ?? null;
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
        if ($sort === '' || $sort === '0') {
            return $this;
        }

        $sorts = explode(',', $sort);
        foreach ($sorts as $field) {
            $field = trim($field);
            if ($field === '') {
                continue;
            }

            if ($field === '0') {
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
            if ($value === null) {
                continue;
            }

            if ($value === '') {
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

        $sql = 'SELECT ' . implode(', ', $this->columns) . (' FROM ' . $this->table);

        if ($this->joins !== []) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if ($this->wheres !== []) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitCount !== null) {
            $sql .= ' LIMIT ' . $this->limitCount;
        }

        if ($this->offsetCount !== null) {
            $sql .= ' OFFSET ' . $this->offsetCount;
        }

        if ($this->forUpdate && $this->supportsForUpdate()) {
            $sql .= ' FOR UPDATE';
        }

        return $sql;
    }

    private function buildWhereClause(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $exprs = [];
        foreach ($this->wheres as $i => $where) {
            if ($i === 0) {
                // Remove OR prefix from first clause if present
                $exprs[] = preg_replace('/^OR\s+/i', '', (string) $where, 1);
            } else {
                $exprs[] = str_starts_with((string) $where, 'OR ') ? $where : 'AND ' . $where;
            }
        }

        return implode(' ', $exprs);
    }

    private function execute(string $sql): array
    {
        $start = hrtime(true);
        $exception = null;

        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $result = $stmt->fetchAll();

            // Log to profiler if available and enabled
            $this->logQueryToProfiler($sql, $start, $exception);

            return $result;
        } catch (PDOException $pdoException) {
            $exception = $pdoException;
            $this->logQueryToProfiler($sql, $start, $exception);
            throw new DbException("Query execution failed: " . $pdoException->getMessage(), $pdoException);
        }
    }

    private function executeUpdate(string $sql): int
    {
        $start = hrtime(true);
        $exception = null;

        try {
            $pdo = $this->connection->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $result = $stmt->rowCount();

            // Log to profiler if available and enabled
            $this->logQueryToProfiler($sql, $start, $exception);

            return $result;
        } catch (PDOException $pdoException) {
            $exception = $pdoException;
            $this->logQueryToProfiler($sql, $start, $exception);
            throw new DbException("Update execution failed: " . $pdoException->getMessage(), $pdoException);
        }
    }

    private function addBinding(mixed $value): string
    {
        $this->bindings[] = $value;
        return '?';
    }

    private function sanitizeColumnName(string $name): string
    {
        // Handle wildcard
        if ($name === '*') {
            return '*';
        }

        $parts = explode('.', $name);
        $out = [];
        $lastIdx = count($parts) - 1;

        foreach ($parts as $i => $segment) {
            // Handle table.* pattern
            if ($i === $lastIdx && $segment === '*') {
                // Previous parts are table identifiers, last is wildcard
                $tableParts = array_slice($parts, 0, $lastIdx);
                foreach ($tableParts as $tableSeg) {
                    if (!preg_match('/^[A-Za-z_]\w*$/', $tableSeg)) {
                        throw new DbException('Invalid identifier segment: ' . $tableSeg);
                    }
                }

                return implode('.', array_map(fn($s): string => sprintf('`%s`', $s), $tableParts)) . '.*';
            }

            // Regular identifier validation
            if (!preg_match('/^[A-Za-z_]\w*$/', $segment)) {
                throw new DbException('Invalid identifier segment: ' . $segment);
            }

            $out[] = sprintf('`%s`', $segment);
        }

        return implode('.', $out);
    }

    private function sanitizeTableName(string $name): string
    {
        if (!preg_match('/^[A-Za-z_]\w*$/', $name)) {
            throw new DbException('Invalid table name: ' . $name);
        }

        return sprintf('`%s`', $name);
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
        return strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }

    /**
     * Check if the current database driver supports FOR UPDATE
     */
    private function supportsForUpdate(): bool
    {
        $driverName = $this->connection->getDriver()->getName();
        
        // SQLite doesn't support FOR UPDATE, MySQL and PostgreSQL do
        return in_array($driverName, ['mysql', 'postgresql', 'pgsql'], true);
    }

    /**
     * Log query to profiler if available and enabled
     */
    private function logQueryToProfiler(string $sql, int $startTime, ?Throwable $exception = null): void
    {
        // Only log if App class exists and profiler is available
        if (!class_exists(App::class)) {
            return;
        }

        try {
            $profiler = App::profiler();
            if ($profiler && $profiler->isEnabled()) {
                $duration = (hrtime(true) - $startTime) / 1_000_000; // Convert to milliseconds
                $profiler->logQuery($sql, $this->bindings, $duration, $exception);
            }
        } catch (Throwable) {
            // Silently ignore profiler errors to avoid disrupting queries
        }
    }
}
