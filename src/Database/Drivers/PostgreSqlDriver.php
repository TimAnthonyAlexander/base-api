<?php

namespace BaseApi\Database\Drivers;

use Override;
use PDOException;
use PDO;
use BaseApi\Database\DbException;
use BaseApi\Database\Migrations\MigrationPlan;
use BaseApi\Database\Migrations\ColumnDef;
use BaseApi\Database\Migrations\IndexDef;
use BaseApi\Database\Migrations\ForeignKeyDef;

class PostgreSqlDriver implements DatabaseDriverInterface
{
    #[Override]
    public function getName(): string
    {
        return 'postgresql';
    }
    
    #[Override]
    public function createConnection(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '5432';
        $database = $config['database'] ?? 'baseapi';
        $username = $config['username'] ?? 'postgres';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8';
        $persistent = ($config['persistent'] ?? false) === true;
        $sslmode = $config['sslmode'] ?? 'prefer';
        $schema = $config['schema'] ?? 'public';

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
        
        // Add SSL mode if specified
        if ($sslmode !== 'disable') {
            $dsn .= ';sslmode=' . $sslmode;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => $persistent,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
            
            // Set timezone to UTC
            $pdo->exec("SET TIME ZONE 'UTC'");
            
            // Set client encoding
            $pdo->exec(sprintf("SET CLIENT_ENCODING TO '%s'", $charset));
            
            // Set search path to include the specified schema
            $pdo->exec(sprintf('SET search_path TO %s, public', $schema));
            
            return $pdo;
        } catch (PDOException $pdoException) {
            throw new DbException("PostgreSQL connection failed: " . $pdoException->getMessage(), $pdoException);
        }
    }
    
    #[Override]
    public function getDatabaseName(PDO $pdo): string
    {
        $stmt = $pdo->query('SELECT current_database()');
        return $stmt->fetchColumn();
    }
    
    #[Override]
    public function getTables(PDO $pdo, string $dbName): array
    {
        $stmt = $pdo->prepare("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_catalog = ? 
            AND table_schema = 'public' 
            AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ");
        $stmt->execute([$dbName]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    #[Override]
    public function getColumns(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                c.column_name,
                c.data_type,
                c.is_nullable,
                c.column_default,
                c.character_maximum_length,
                c.numeric_precision,
                c.numeric_scale,
                c.udt_name,
                CASE 
                    WHEN pk.column_name IS NOT NULL THEN true 
                    ELSE false 
                END as is_primary_key
            FROM information_schema.columns c
            LEFT JOIN (
                SELECT ku.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage ku 
                    ON tc.constraint_name = ku.constraint_name
                WHERE tc.table_catalog = ? 
                AND tc.table_name = ? 
                AND tc.constraint_type = 'PRIMARY KEY'
            ) pk ON c.column_name = pk.column_name
            WHERE c.table_catalog = ? 
            AND c.table_name = ?
            ORDER BY c.ordinal_position
        ");
        $stmt->execute([$dbName, $tableName, $dbName, $tableName]);
        
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['column_name']] = new ColumnDef(
                name: $row['column_name'],
                type: $this->normalizeColumnType($row['data_type'], $row['udt_name']),
                nullable: $row['is_nullable'] === 'YES',
                default: $this->normalizeDefault($row['column_default']),
                is_pk: (bool)$row['is_primary_key']
            );
        }
        
        return $columns;
    }
    
    #[Override]
    public function getIndexes(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                i.indexname as index_name,
                a.attname as column_name,
                i.indexdef,
                CASE 
                    WHEN i.indexdef LIKE '%UNIQUE%' THEN true 
                    ELSE false 
                END as is_unique
            FROM pg_indexes i
            JOIN pg_class c ON c.relname = i.tablename
            JOIN pg_index idx ON idx.indexrelid = (
                SELECT oid FROM pg_class WHERE relname = i.indexname
            )
            JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum = ANY(idx.indkey)
            WHERE i.schemaname = 'public' 
            AND i.tablename = ?
            AND i.indexname NOT LIKE '%_pkey'
            ORDER BY i.indexname
        ");
        $stmt->execute([$tableName]);
        
        $indexes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $indexes[$row['index_name']] = new IndexDef(
                name: $row['index_name'],
                column: $row['column_name'],
                type: $row['is_unique'] ? 'unique' : 'index'
            );
        }
        
        return $indexes;
    }
    
    #[Override]
    public function getForeignKeys(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name,
                rc.update_rule,
                rc.delete_rule
            FROM information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints AS rc
                ON tc.constraint_name = rc.constraint_name
                AND tc.table_schema = rc.constraint_schema
            WHERE tc.constraint_type = 'FOREIGN KEY' 
            AND tc.table_name = ?
        ");
        $stmt->execute([$tableName]);
        
        $fks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fks[$row['constraint_name']] = new ForeignKeyDef(
                name: $row['constraint_name'],
                column: $row['column_name'],
                ref_table: $row['foreign_table_name'],
                ref_column: $row['foreign_column_name'],
                on_delete: strtoupper((string) $row['delete_rule']),
                on_update: strtoupper((string) $row['update_rule'])
            );
        }
        
        return $fks;
    }
    
    #[Override]
    public function generateSql(MigrationPlan $plan): array
    {
        $statements = [];
        
        // Group operations by type for proper ordering
        $createTables = [];
        $addColumns = [];
        $modifyColumns = [];
        $addIndexes = [];
        $addFks = [];
        $dropFks = [];
        $dropIndexes = [];
        $dropColumns = [];
        $dropTables = [];
        
        foreach ($plan->operations as $op) {
            match ($op['op']) {
                'create_table' => $createTables[] = $op,
                'add_column' => $addColumns[] = $op,
                'modify_column' => $modifyColumns[] = $op,
                'add_index' => $addIndexes[] = $op,
                'add_fk' => $addFks[] = $op,
                'drop_fk' => $dropFks[] = $op,
                'drop_index' => $dropIndexes[] = $op,
                'drop_column' => $dropColumns[] = $op,
                'drop_table' => $dropTables[] = $op,
                default => null
            };
        }
        
        // Execute in proper order: creates → adds → drops
        foreach ($createTables as $op) {
            $statements[] = $this->generateCreateTable($op);
        }
        
        foreach ($addColumns as $op) {
            $statements[] = $this->generateAddColumn($op);
        }
        
        foreach ($modifyColumns as $op) {
            $statements[] = $this->generateModifyColumn($op);
        }
        
        foreach ($addIndexes as $op) {
            $statements[] = $this->generateAddIndex($op);
        }
        
        foreach ($addFks as $op) {
            $statements[] = $this->generateAddForeignKey($op);
        }
        
        // Drops happen last
        foreach ($dropFks as $op) {
            $statements[] = $this->generateDropForeignKey($op);
        }
        
        foreach ($dropIndexes as $op) {
            $statements[] = $this->generateDropIndex($op);
        }
        
        foreach ($dropColumns as $op) {
            $statements[] = $this->generateDropColumn($op);
        }
        
        foreach ($dropTables as $op) {
            $statements[] = $this->generateDropTable($op);
        }
        
        return array_filter($statements);
    }
    
    private function generateCreateTable(array $op): array
    {
        $tableName = $op['table'];
        $columns = $op['columns'];
        
        $columnDefs = [];
        $primaryKeys = [];
        
        foreach ($columns as $columnData) {
            $column = ColumnDef::fromArray($columnData);
            $columnDefs[] = $this->generateColumnDefinition($column);
            
            if ($column->is_pk) {
                $primaryKeys[] = $column->name;
            }
        }
        
        $sql = "CREATE TABLE \"{$tableName}\" (\n";
        $sql .= "  " . implode(",\n  ", $columnDefs);
        
        if ($primaryKeys !== []) {
            $sql .= ",\n  PRIMARY KEY (\"" . implode('", "', $primaryKeys) . '")';
        }
        
        $sql .= "\n)";
        
        return [
            'sql' => $sql,
            'description' => 'Create table ' . $tableName,
            'destructive' => false
        ];
    }
    
    private function generateAddColumn(array $op): array
    {
        $tableName = $op['table'];
        $column = ColumnDef::fromArray($op['column']);
        
        $sql = sprintf('ALTER TABLE "%s" ADD COLUMN ', $tableName) . $this->generateColumnDefinition($column);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Add column %s to %s', $column->name, $tableName),
            'destructive' => false
        ];
    }
    
    private function generateModifyColumn(array $op): array
    {
        $tableName = $op['table'];
        $column = ColumnDef::fromArray($op['column']);
        
        // PostgreSQL requires separate ALTER statements for different column properties
        $statements = [];
        
        // Change data type
        $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" TYPE %s', $tableName, $column->name, $column->type);
        
        // Change nullability
        if ($column->nullable) {
            $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" DROP NOT NULL', $tableName, $column->name);
        } else {
            $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" SET NOT NULL', $tableName, $column->name);
        }
        
        // Change default value
        if ($column->default !== null) {
            if ($column->default === 'CURRENT_TIMESTAMP') {
                $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" SET DEFAULT CURRENT_TIMESTAMP', $tableName, $column->name);
            } else {
                $statements[] = sprintf("ALTER TABLE \"%s\" ALTER COLUMN \"%s\" SET DEFAULT '%s'", $tableName, $column->name, $column->default);
            }
        } else {
            $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" DROP DEFAULT', $tableName, $column->name);
        }
        
        return [
            'sql' => implode(";\n", $statements),
            'description' => sprintf('Modify column %s in %s', $column->name, $tableName),
            'destructive' => $op['destructive'] ?? false
        ];
    }
    
    private function generateAddIndex(array $op): array
    {
        $tableName = $op['table'];
        $index = IndexDef::fromArray($op['index']);
        
        if ($index->type === 'unique') {
            $sql = sprintf('CREATE UNIQUE INDEX "%s" ON "%s" ("%s")', $index->name, $tableName, $index->column);
        } else {
            $sql = sprintf('CREATE INDEX "%s" ON "%s" ("%s")', $index->name, $tableName, $index->column);
        }
        
        return [
            'sql' => $sql,
            'description' => sprintf('Add %s index %s to %s', $index->type, $index->name, $tableName),
            'destructive' => false
        ];
    }
    
    private function generateAddForeignKey(array $op): array
    {
        $tableName = $op['table'];
        $fk = ForeignKeyDef::fromArray($op['fk']);
        
        $sql = sprintf('ALTER TABLE "%s" ADD CONSTRAINT "%s" ', $tableName, $fk->name) .
               sprintf('FOREIGN KEY ("%s") REFERENCES "%s" ("%s") ', $fk->column, $fk->ref_table, $fk->ref_column) .
               sprintf('ON DELETE %s ON UPDATE %s', $fk->on_delete, $fk->on_update);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Add foreign key %s to %s', $fk->name, $tableName),
            'destructive' => false
        ];
    }
    
    private function generateDropForeignKey(array $op): array
    {
        $tableName = $op['table'];
        $fkName = $op['fk_name'];
        
        $sql = sprintf('ALTER TABLE "%s" DROP CONSTRAINT "%s"', $tableName, $fkName);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Drop foreign key %s from %s', $fkName, $tableName),
            'destructive' => true
        ];
    }
    
    private function generateDropIndex(array $op): array
    {
        $indexName = $op['index'];
        $tableName = $op['table'] ?? 'unknown';
        
        $sql = sprintf('DROP INDEX IF EXISTS "%s"', $indexName);
        
        return [
            'sql' => $sql,
            'description' => 'Drop index ' . $indexName,
            'destructive' => true,
            'table' => $tableName
        ];
    }
    
    private function generateDropColumn(array $op): array
    {
        $tableName = $op['table'];
        $columnName = $op['column_name'];
        
        $sql = sprintf('ALTER TABLE "%s" DROP COLUMN "%s"', $tableName, $columnName);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Drop column %s from %s', $columnName, $tableName),
            'destructive' => true
        ];
    }
    
    private function generateDropTable(array $op): array
    {
        $tableName = $op['table'];
        
        $sql = sprintf('DROP TABLE IF EXISTS "%s"', $tableName);
        
        return [
            'sql' => $sql,
            'description' => 'Drop table ' . $tableName,
            'destructive' => true,
            'table' => $tableName
        ];
    }
    
    private function generateColumnDefinition(ColumnDef $column): string
    {
        $sql = sprintf('"%s" %s', $column->name, $column->type);
        
        if (!$column->nullable) {
            $sql .= ' NOT NULL';
        }
        
        if ($column->default !== null) {
            if ($column->default === 'CURRENT_TIMESTAMP') {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } elseif (str_starts_with($column->default, 'nextval(')) {
                // Handle SERIAL/BIGSERIAL defaults
                $sql .= ' DEFAULT ' . $column->default;
            } else {
                $sql .= sprintf(" DEFAULT '%s'", $column->default);
            }
        }
        
        return $sql;
    }
    
    #[Override]
    public function normalizeColumnType(string $columnType, string $udtName = ''): string
    {
        // Convert PostgreSQL types to our normalized format
        $type = strtolower(trim($columnType));
        
        // Handle user-defined types (like SERIAL, BIGSERIAL)
        if ($udtName !== '' && $udtName !== '0') {
            $udtName = strtolower($udtName);
            if (in_array($udtName, ['int4', 'serial4'])) {
                return 'integer';
            }

            if (in_array($udtName, ['int8', 'bigserial', 'serial8'])) {
                return 'bigint';
            }
        }
        
        return match ($type) {
            'boolean', 'bool' => 'boolean',
            'smallint', 'int2' => 'smallint',
            'integer', 'int', 'int4', 'serial' => 'integer',
            'bigint', 'int8', 'bigserial' => 'bigint',
            'decimal', 'numeric' => 'decimal',
            'real', 'float4' => 'real',
            'double precision', 'float8' => 'double',
            'character', 'char' => 'char',
            'character varying', 'varchar' => 'varchar',
            'text' => 'text',
            'bytea' => 'bytea',
            'date' => 'date',
            'time', 'time without time zone' => 'time',
            'timestamp', 'timestamp without time zone' => 'timestamp',
            'timestamp with time zone', 'timestamptz' => 'timestamptz',
            'interval' => 'interval',
            'json' => 'json',
            'jsonb' => 'jsonb',
            'uuid' => 'uuid',
            'inet' => 'inet',
            'cidr' => 'cidr',
            'macaddr' => 'macaddr',
            'array' => 'array',
            default => $columnType
        };
    }
    
    #[Override]
    public function normalizeDefault(?string $default, string $extra = ''): ?string
    {
        if ($default === null || $default === 'NULL') {
            return null;
        }
        
        // Handle PostgreSQL-specific defaults
        if ($default === 'CURRENT_TIMESTAMP' || $default === 'now()') {
            return 'CURRENT_TIMESTAMP';
        }
        
        // Handle SERIAL/BIGSERIAL sequence defaults
        if (str_contains($default, 'nextval(')) {
            return $default; // Keep as-is for sequences
        }
        
        // Handle boolean defaults
        if ($default === 'true' || $default === 'false') {
            return $default;
        }
        
        // Remove quotes and handle string defaults
        $default = trim($default, "'\"");
        
        // Handle special PostgreSQL syntax
        if (str_ends_with($default, '::text') || str_ends_with($default, '::varchar')) {
            $default = substr($default, 0, strrpos($default, '::'));
            $default = trim($default, "'\"");
        }
        
        return $default;
    }
    
    #[Override]
    public function phpTypeToSqlType(string $phpType, string $propertyName = ''): string
    {
        return match ($phpType) {
            'bool', 'boolean' => 'BOOLEAN',
            'int', 'integer' => str_contains($propertyName, 'id') || str_ends_with($propertyName, '_id') ? 'SERIAL' : 'INTEGER',
            'float' => 'REAL',
            'double' => 'DOUBLE PRECISION',
            'string' => str_contains($propertyName, 'id') || str_ends_with($propertyName, '_id') ? 'UUID' : 'VARCHAR(255)',
            'array', 'object' => 'JSONB',
            'DateTime', '\\DateTime' => 'TIMESTAMP',
            default => 'TEXT'
        };
    }
}
