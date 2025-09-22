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

class MySqlDriver implements DatabaseDriverInterface
{
    #[Override]
    public function getName(): string
    {
        return 'mysql';
    }
    
    #[Override]
    public function createConnection(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? 'baseapi';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $persistent = ($config['persistent'] ?? false) === true;

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => $persistent,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);

            // Set timezone to UTC
            $pdo->exec("SET time_zone = '+00:00'");

            // Set names (charset)
            $pdo->exec('SET NAMES ' . $charset);
            
            return $pdo;
        } catch (PDOException $pdoException) {
            throw new DbException("Database connection failed: " . $pdoException->getMessage(), $pdoException);
        }
    }
    
    #[Override]
    public function getDatabaseName(PDO $pdo): string
    {
        $stmt = $pdo->query('SELECT DATABASE()');
        return $stmt->fetchColumn();
    }
    
    #[Override]
    public function getTables(PDO $pdo, string $dbName): array
    {
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_TYPE = 'BASE TABLE'
        ");
        $stmt->execute([$dbName]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    #[Override]
    public function getColumns(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                COLUMN_NAME,
                DATA_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                EXTRA,
                COLUMN_KEY
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $stmt->execute([$dbName, $tableName]);
        
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['COLUMN_NAME']] = new ColumnDef(
                name: $row['COLUMN_NAME'],
                type: $this->normalizeColumnType($row['DATA_TYPE']),
                nullable: $row['IS_NULLABLE'] === 'YES',
                default: $this->normalizeDefault($row['COLUMN_DEFAULT'], $row['EXTRA']),
                is_pk: $row['COLUMN_KEY'] === 'PRI'
            );
        }
        
        return $columns;
    }
    
    #[Override]
    public function getIndexes(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                INDEX_NAME,
                COLUMN_NAME,
                NON_UNIQUE
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            AND INDEX_NAME != 'PRIMARY'
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ");
        $stmt->execute([$dbName, $tableName]);
        
        $indexes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $indexes[$row['INDEX_NAME']] = new IndexDef(
                name: $row['INDEX_NAME'],
                column: $row['COLUMN_NAME'],
                type: $row['NON_UNIQUE'] ? 'index' : 'unique'
            );
        }
        
        return $indexes;
    }
    
    #[Override]
    public function getForeignKeys(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                kcu.CONSTRAINT_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc 
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ?
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$dbName, $tableName]);
        
        $fks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fks[$row['CONSTRAINT_NAME']] = new ForeignKeyDef(
                name: $row['CONSTRAINT_NAME'],
                column: $row['COLUMN_NAME'],
                ref_table: $row['REFERENCED_TABLE_NAME'],
                ref_column: $row['REFERENCED_COLUMN_NAME'],
                on_delete: $row['DELETE_RULE'],
                on_update: $row['UPDATE_RULE']
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
        $primaryKey = null;
        
        foreach ($columns as $columnData) {
            $column = ColumnDef::fromArray($columnData);
            $columnDefs[] = $this->generateColumnDefinition($column);
            
            if ($column->is_pk) {
                $primaryKey = $column->name;
            }
        }
        
        $sql = "CREATE TABLE `{$tableName}` (\n";
        $sql .= "  " . implode(",\n  ", $columnDefs);
        
        if ($primaryKey) {
            $sql .= ",\n  PRIMARY KEY (`{$primaryKey}`)";
        }
        
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
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
        
        $sql = sprintf('ALTER TABLE `%s` ADD COLUMN ', $tableName) . $this->generateColumnDefinition($column);
        
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
        
        $sql = sprintf('ALTER TABLE `%s` MODIFY COLUMN ', $tableName) . $this->generateColumnDefinition($column);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Modify column %s in %s', $column->name, $tableName),
            'destructive' => $op['destructive'] ?? false
        ];
    }
    
    private function generateAddIndex(array $op): array
    {
        $tableName = $op['table'];
        $index = IndexDef::fromArray($op['index']);
        
        if ($index->type === 'unique') {
            $sql = sprintf('ALTER TABLE `%s` ADD UNIQUE KEY `%s` (`%s`)', $tableName, $index->name, $index->column);
        } else {
            $sql = sprintf('ALTER TABLE `%s` ADD INDEX `%s` (`%s`)', $tableName, $index->name, $index->column);
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
        
        $sql = sprintf('ALTER TABLE `%s` ADD CONSTRAINT `%s` ', $tableName, $fk->name) .
               sprintf('FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ', $fk->column, $fk->ref_table, $fk->ref_column) .
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
        
        $sql = sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $tableName, $fkName);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Drop foreign key %s from %s', $fkName, $tableName),
            'destructive' => true
        ];
    }
    
    private function generateDropIndex(array $op): array
    {
        $tableName = $op['table'];
        $indexName = $op['index'];
        
        $sql = sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $tableName, $indexName);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Drop index %s from %s', $indexName, $tableName),
            'destructive' => true,
            'table' => $tableName
        ];
    }
    
    private function generateDropColumn(array $op): array
    {
        $tableName = $op['table'];
        $columnName = $op['column'];
        
        $sql = sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $tableName, $columnName);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Drop column %s from %s', $columnName, $tableName),
            'destructive' => true
        ];
    }
    
    private function generateDropTable(array $op): array
    {
        $tableName = $op['table'];
        
        $sql = sprintf('DROP TABLE IF EXISTS `%s`', $tableName);
        
        return [
            'sql' => $sql,
            'description' => 'Drop table ' . $tableName,
            'destructive' => true,
            'table' => $tableName
        ];
    }
    
    private function generateColumnDefinition(ColumnDef $column): string
    {
        $sql = sprintf('`%s` %s', $column->name, $column->type);
        
        if (!$column->nullable) {
            $sql .= ' NOT NULL';
        }
        
        if ($column->default !== null) {
            if ($column->default === 'CURRENT_TIMESTAMP') {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } elseif ($column->default === 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            } else {
                $sql .= sprintf(" DEFAULT '%s'", $column->default);
            }
        }
        
        return $sql;
    }
    
    #[Override]
    public function normalizeColumnType(string $columnType): string
    {
        // Convert MySQL types to our normalized format
        return match (strtolower($columnType)) {
            'tinyint' => 'boolean',
            'smallint', 'mediumint', 'int', 'integer' => 'integer',
            'bigint' => 'bigint',
            'decimal', 'numeric' => 'decimal',
            'float' => 'float',
            'double', 'real' => 'double',
            'char' => 'char',
            'varchar' => 'varchar',
            'text', 'tinytext', 'mediumtext', 'longtext' => 'text',
            'binary', 'varbinary' => 'binary',
            'blob', 'tinyblob', 'mediumblob', 'longblob' => 'blob',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'year' => 'year',
            'json' => 'json',
            'enum' => 'enum',
            'set' => 'set',
            default => $columnType
        };
    }
    
    #[Override]
    public function normalizeDefault(?string $default, string $extra): ?string
    {
        if ($default === null) {
            return null;
        }
        
        // Handle special MySQL defaults
        if ($default === 'CURRENT_TIMESTAMP' || str_contains($extra, 'DEFAULT_GENERATED')) {
            return 'CURRENT_TIMESTAMP';
        }
        
        // Remove quotes from string defaults
        return trim($default, "'\"");
    }
    
    #[Override]
    public function phpTypeToSqlType(string $phpType, string $propertyName = ''): string
    {
        return match ($phpType) {
            'bool', 'boolean' => 'TINYINT(1)',
            'int', 'integer' => 'INT',
            'float', 'double' => 'DOUBLE',
            'string' => str_contains($propertyName, 'id') || str_ends_with($propertyName, '_id') ? 'VARCHAR(36)' : 'VARCHAR(255)',
            'array', 'object' => 'JSON',
            'DateTime', '\\DateTime' => 'DATETIME',
            default => 'VARCHAR(255)'
        };
    }
}
