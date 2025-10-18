<?php

namespace BaseApi\Database\Drivers;

use Override;
use PDOException;
use SQLite3;
use PDO;
use BaseApi\App;
use BaseApi\Database\DbException;
use BaseApi\Database\Migrations\MigrationPlan;
use BaseApi\Database\Migrations\ColumnDef;
use BaseApi\Database\Migrations\IndexDef;
use BaseApi\Database\Migrations\ForeignKeyDef;

class SqliteDriver implements DatabaseDriverInterface
{
    #[Override]
    public function getName(): string
    {
        return 'sqlite';
    }
    
    #[Override]
    public function createConnection(array $config): PDO
    {
        $database = $config['database'] ?? ':memory:';
        
        // If database is not :memory: and doesn't start with /, make it relative to storage
        if ($database !== ':memory:' && !str_starts_with((string) $database, '/')) {
            $database = App::storagePath($database);
            
            // Create directory if it doesn't exist
            $dir = dirname($database);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $dsn = 'sqlite:' . $database;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, null, null, $options);
            
            // Enable foreign key constraints
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            // Set journal mode to WAL for better concurrency
            $pdo->exec('PRAGMA journal_mode = WAL');
            
            return $pdo;
        } catch (PDOException $pdoException) {
            throw new DbException("SQLite connection failed: " . $pdoException->getMessage(), $pdoException);
        }
    }
    
    #[Override]
    public function getDatabaseName(PDO $pdo): string
    {
        // SQLite doesn't have a concept of database name like MySQL
        // Return a default name or the file path
        $stmt = $pdo->query("PRAGMA database_list");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['file'] ?? 'main';
    }
    
    #[Override]
    public function getTables(PDO $pdo, string $dbName): array
    {
        $stmt = $pdo->query("
            SELECT name FROM sqlite_master 
            WHERE type='table' 
            AND name NOT LIKE 'sqlite_%'
            ORDER BY name
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    #[Override]
    public function getColumns(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare(sprintf('PRAGMA table_info("%s")', $tableName));
        $stmt->execute();
        
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['name']] = new ColumnDef(
                name: $row['name'],
                type: $this->normalizeFullColumnType($row['type']),
                nullable: !$row['notnull'],
                default: $this->normalizeDefault($row['dflt_value'], ''),
                is_pk: (bool)$row['pk']
            );
        }
        
        return $columns;
    }
    
    #[Override]
    public function getIndexes(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare(sprintf('PRAGMA index_list("%s")', $tableName));
        $stmt->execute();
        
        $indexes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Skip auto-created primary key indexes
            if (str_starts_with((string) $row['name'], 'sqlite_autoindex_')) {
                continue;
            }
            
            // Get index info to find the column
            $infoStmt = $pdo->prepare(sprintf('PRAGMA index_info("%s")', $row['name']));
            $infoStmt->execute();
            $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($info) {
                $indexes[$row['name']] = new IndexDef(
                    name: $row['name'],
                    column: $info['name'],
                    type: $row['unique'] ? 'unique' : 'index'
                );
            }
        }
        
        return $indexes;
    }
    
    #[Override]
    public function getForeignKeys(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare(sprintf('PRAGMA foreign_key_list("%s")', $tableName));
        $stmt->execute();
        
        $fks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Generate a name since SQLite doesn't store FK names
            $name = sprintf('fk_%s_%s_%s_%s', $tableName, $row['from'], $row['table'], $row['to']);
            
            $fks[$name] = new ForeignKeyDef(
                name: $name,
                column: $row['from'],
                ref_table: $row['table'],
                ref_column: $row['to'],
                on_delete: $row['on_delete'],
                on_update: $row['on_update']
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
        // For SQLite, collect foreign keys for new tables since they must be defined in CREATE TABLE
        $tableFks = [];
        foreach ($addFks as $op) {
            $tableName = $op['table'] ?? '';
            if (!isset($tableFks[$tableName])) {
                $tableFks[$tableName] = [];
            }

            if (isset($op['fk'])) {
                $tableFks[$tableName][] = $op['fk'];
            }
        }
        
        foreach ($createTables as $op) {
            $tableName = $op['table'];
            $fks = $tableFks[$tableName] ?? [];
            $statements[] = $this->generateCreateTable($op, $fks);
        }
        
        foreach ($addColumns as $op) {
            $statements[] = $this->generateAddColumn($op);
        }
        
        foreach ($modifyColumns as $op) {
            // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
            $statements = array_merge($statements, $this->generateModifyColumn());
        }
        
        foreach ($addIndexes as $op) {
            $statements[] = $this->generateAddIndex($op);
        }
        
        foreach ($addFks as $op) {
            // SQLite foreign keys are defined at table creation time
            // Skip FKs for tables that are being created (handled in CREATE TABLE)
            $tableName = $op['table'];
            $isNewTable = false;
            foreach ($createTables as $createOp) {
                if ($createOp['table'] === $tableName) {
                    $isNewTable = true;
                    break;
                }
            }
            
            if (!$isNewTable) {
                // For existing tables, we would need table recreation
                // This is complex and not implemented yet
                // For now, skip adding FKs to existing tables
            }
        }
        
        // Drops happen last
        foreach ($dropFks as $op) {
            // SQLite doesn't support dropping foreign keys
            // Would require table recreation
        }
        
        foreach ($dropIndexes as $op) {
            $statements[] = $this->generateDropIndex($op);
        }
        
        foreach ($dropColumns as $op) {
            // SQLite doesn't support DROP COLUMN (before 3.35.0)
            // Would require table recreation
            $statements = array_merge($statements, $this->generateDropColumn($op));
        }
        
        foreach ($dropTables as $op) {
            $statements[] = $this->generateDropTable($op);
        }
        
        return array_filter($statements);
    }
    
    private function generateCreateTable(array $op, array $fks = []): array
    {
        $tableName = $op['table'];
        $columns = $op['columns'];
        
        $columnDefs = [];
        $primaryKeys = [];
        
        foreach ($columns as $columnData) {
            $column = ColumnDef::fromArray($columnData);
            if ($column->is_pk) {
                $primaryKeys[] = $column->name;
            }
        }
        
        // For SQLite, if there's only one primary key, use column-level PRIMARY KEY
        // If there are multiple, use table-level PRIMARY KEY constraint
        $useColumnLevelPK = count($primaryKeys) === 1;
        
        foreach ($columns as $columnData) {
            $column = ColumnDef::fromArray($columnData);
            $columnDefs[] = $this->generateColumnDefinition($column, $useColumnLevelPK);
        }
        
        $sql = "CREATE TABLE \"{$tableName}\" (\n";
        $sql .= "  " . implode(",\n  ", $columnDefs);
        
        // Only add table-level PRIMARY KEY if there are multiple primary key columns
        if ($primaryKeys !== [] && !$useColumnLevelPK) {
            $sql .= ",\n  PRIMARY KEY (\"" . implode('", "', $primaryKeys) . '")';
        }
        
        // Add foreign key constraints
        foreach ($fks as $fkData) {
            $fk = ForeignKeyDef::fromArray($fkData);
            $sql .= ",\n  FOREIGN KEY (\"{$fk->column}\") REFERENCES \"{$fk->ref_table}\" (\"{$fk->ref_column}\")";

            // Add ON DELETE and ON UPDATE clauses if specified
            if ($fk->on_delete && $fk->on_delete !== 'NO ACTION') {
                $sql .= ' ON DELETE ' . $fk->on_delete;
            }

            if ($fk->on_update && $fk->on_update !== 'NO ACTION') {
                $sql .= ' ON UPDATE ' . $fk->on_update;
            }
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
        
        $warning = null;
        
        // Handle the case where we're adding a NOT NULL column without a default value
        // This would fail on existing tables, so we need to provide a sensible default
        if (!$column->nullable && $column->default === null && !$column->is_pk) {
            $autoDefault = $this->getDefaultValueForType($column->type);
            $column->default = $autoDefault;
            $warning = sprintf("Auto-generated default value '%s' for NOT NULL column without explicit default", $autoDefault);
        }
        
        $sql = sprintf('ALTER TABLE "%s" ADD COLUMN ', $tableName) . $this->generateColumnDefinition($column, true);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Add column %s to %s', $column->name, $tableName),
            'destructive' => false,
            'warning' => $warning
        ];
    }
    
    private function generateModifyColumn(): array
    {
        // SQLite doesn't support ALTER COLUMN directly
        // This would require table recreation which is complex
        // For now, return empty array - this can be implemented later if needed
        return [];
    }
    
    private function generateAddIndex(array $op): array
    {
        $tableName = $op['table'];
        $index = IndexDef::fromArray($op['index']);
        
        $unique = $index->type === 'unique' ? 'UNIQUE ' : '';
        $sql = sprintf('CREATE %sINDEX "%s" ON "%s" ("%s")', $unique, $index->name, $tableName, $index->column);
        
        return [
            'sql' => $sql,
            'description' => sprintf('Add %s index %s to %s', $index->type, $index->name, $tableName),
            'destructive' => false
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
        $columnName = $op['column'];
        
        // Check SQLite version to see if DROP COLUMN is supported
        // SQLite 3.35.0+ supports DROP COLUMN natively
        $version = $this->getSqliteVersion();
        if (version_compare($version, '3.35.0', '>=')) {
            $sql = sprintf('ALTER TABLE "%s" DROP COLUMN "%s"', $tableName, $columnName);
            return [
                [
                    'sql' => $sql,
                    'destructive' => true,
                    'warning' => 'Dropping column - data will be lost'
                ]
            ];
        }
        
        // For older SQLite versions, dropping columns requires table recreation
        // This is complex and potentially dangerous, so we'll warn but skip for now
        return [
            [
                'sql' => sprintf('-- DROP COLUMN "%s" FROM "%s" (Not supported in SQLite < 3.35.0)', $columnName, $tableName),
                'destructive' => true,
                'warning' => 'Column drop not implemented for SQLite < 3.35.0. Manual table recreation required.'
            ]
        ];
    }
    
    private function getSqliteVersion(): string
    {
        return SQLite3::version()['versionString'] ?? '3.0.0';
    }

    /**
     * Get a sensible default value for a given SQLite column type
     */
    private function getDefaultValueForType(string $type): string
    {
        $type = strtoupper($type);
        
        // Handle common SQLite types and provide appropriate defaults
        if (str_contains($type, 'INT') || str_contains($type, 'BOOL')) {
            return '0';
        }
        
        if (str_contains($type, 'REAL') || str_contains($type, 'FLOAT') || str_contains($type, 'DOUBLE')) {
            return '0.0';
        }
        
        if (str_contains($type, 'TEXT') || str_contains($type, 'CHAR') || str_contains($type, 'VARCHAR')) {
            return '';
        }
        
        if (str_contains($type, 'BLOB')) {
            return '';
        }
        
        if (str_contains($type, 'DATE') || str_contains($type, 'TIME')) {
            return 'CURRENT_TIMESTAMP';
        }
        
        // Default fallback for unknown types
        return '';
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
    
    private function generateColumnDefinition(ColumnDef $column, bool $useColumnLevelPK = true): string
    {
        $sql = sprintf('"%s" %s', $column->name, $column->type);
        
        // Only add column-level PRIMARY KEY if we're using column-level PKs and this is a PK
        if ($column->is_pk && $useColumnLevelPK) {
            $sql .= ' PRIMARY KEY';
        }
        
        if (!$column->nullable) {
            $sql .= ' NOT NULL';
        }
        
        if ($column->default !== null) {
            if ($column->default === 'CURRENT_TIMESTAMP') {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } elseif ($column->default === 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') {
                // SQLite doesn't support ON UPDATE, just use CURRENT_TIMESTAMP
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } else {
                // Normalize boolean values to string representation
                $defaultValue = $column->default;
                if (is_bool($defaultValue)) {
                    $defaultValue = $defaultValue ? '1' : '0';
                }
                
                // Don't quote numeric defaults
                if (is_numeric($defaultValue)) {
                    $sql .= sprintf(" DEFAULT %s", $defaultValue);
                } else {
                    $sql .= sprintf(" DEFAULT '%s'", $defaultValue);
                }
            }
        }
        
        return $sql;
    }
    
    /**
     * Normalize full column type from SQLite PRAGMA table_info (e.g., "VARCHAR(255)", "INTEGER")
     * This preserves the length/precision information needed for accurate comparisons
     */
    private function normalizeFullColumnType(string $columnType): string
    {
        // SQLite stores the full type definition (e.g., "VARCHAR(255)", "INTEGER")
        // Just normalize to uppercase for consistency
        return strtoupper(trim($columnType));
    }
    
    #[Override]
    public function normalizeColumnType(string $columnType): string
    {
        // Convert SQLite types to our normalized format
        $type = strtolower(trim($columnType));

        // SQLite is flexible with types, so we need to be more liberal
        if (str_contains($type, 'int')) {
            return 'integer';
        }

        if (str_contains($type, 'char') || str_contains($type, 'text')) {
            return 'text';
        }

        if (str_contains($type, 'blob')) {
            return 'blob';
        }

        if (str_contains($type, 'real') || str_contains($type, 'floa') || str_contains($type, 'doub')) {
            return 'real';
        }

        return match ($type) {
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'json' => 'text', // SQLite stores JSON as TEXT
            default => $type
        };
    }
    
    #[Override]
    public function normalizeDefault(?string $default, string $extra): ?string
    {
        if ($default === null || $default === 'NULL') {
            return null;
        }
        
        // Remove quotes from string defaults
        $default = trim($default, "'\"");
        
        if ($default === 'CURRENT_TIMESTAMP') {
            return 'CURRENT_TIMESTAMP';
        }
        
        return $default;
    }
    
    #[Override]
    public function phpTypeToSqlType(string $phpType, string $propertyName = ''): string
    {
        return match ($phpType) {
            'bool', 'boolean' => 'INTEGER', // SQLite uses INTEGER for boolean
            'int', 'integer' => 'INTEGER',
            'float', 'double' => 'REAL',
            'string' => str_contains($propertyName, 'id') || str_ends_with($propertyName, '_id') ? 'TEXT' : 'TEXT',
            'array', 'object' => 'TEXT', // Store as JSON text
            'DateTime', '\\DateTime' => 'TEXT', // SQLite stores datetime as text
            default => 'TEXT'
        };
    }
}
