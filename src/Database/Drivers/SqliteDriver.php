<?php

namespace BaseApi\Database\Drivers;

use PDO;
use BaseApi\Database\DbException;
use BaseApi\Database\Migrations\MigrationPlan;
use BaseApi\Database\Migrations\ColumnDef;
use BaseApi\Database\Migrations\IndexDef;
use BaseApi\Database\Migrations\ForeignKeyDef;

class SqliteDriver implements DatabaseDriverInterface
{
    public function getName(): string
    {
        return 'sqlite';
    }
    
    public function createConnection(array $config): PDO
    {
        $database = $config['database'] ?? ':memory:';
        
        // If database is not :memory: and doesn't start with /, make it relative to storage
        if ($database !== ':memory:' && !str_starts_with($database, '/')) {
            $database = getcwd() . '/storage/' . $database;
            
            // Create directory if it doesn't exist
            $dir = dirname($database);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $dsn = "sqlite:{$database}";

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
        } catch (\PDOException $e) {
            throw new DbException("SQLite connection failed: " . $e->getMessage(), $e);
        }
    }
    
    public function getDatabaseName(PDO $pdo): string
    {
        // SQLite doesn't have a concept of database name like MySQL
        // Return a default name or the file path
        $stmt = $pdo->query("PRAGMA database_list");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['file'] ?? 'main';
    }
    
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
    
    public function getColumns(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("PRAGMA table_info(\"{$tableName}\")");
        $stmt->execute();
        
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['name']] = new ColumnDef(
                name: $row['name'],
                type: $this->normalizeColumnType($row['type']),
                nullable: !$row['notnull'],
                default: $this->normalizeDefault($row['dflt_value'], ''),
                is_pk: (bool)$row['pk']
            );
        }
        
        return $columns;
    }
    
    public function getIndexes(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("PRAGMA index_list(\"{$tableName}\")");
        $stmt->execute();
        
        $indexes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Skip auto-created primary key indexes
            if (str_starts_with($row['name'], 'sqlite_autoindex_')) {
                continue;
            }
            
            // Get index info to find the column
            $infoStmt = $pdo->prepare("PRAGMA index_info(\"{$row['name']}\")");
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
    
    public function getForeignKeys(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("PRAGMA foreign_key_list(\"{$tableName}\")");
        $stmt->execute();
        
        $fks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Generate a name since SQLite doesn't store FK names
            $name = "fk_{$tableName}_{$row['from']}_{$row['table']}_{$row['to']}";
            
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
            $tableName = $op['table'];
            if (!isset($tableFks[$tableName])) {
                $tableFks[$tableName] = [];
            }
            $tableFks[$tableName][] = $op['fk'];
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
            $statements = array_merge($statements, $this->generateModifyColumn($op));
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
        if (!empty($primaryKeys) && !$useColumnLevelPK) {
            $sql .= ",\n  PRIMARY KEY (\"" . implode('", "', $primaryKeys) . "\")";
        }
        
        // Add foreign key constraints
        foreach ($fks as $fkData) {
            $fk = ForeignKeyDef::fromArray($fkData);
            $sql .= ",\n  FOREIGN KEY (\"{$fk->column}\") REFERENCES \"{$fk->ref_table}\" (\"{$fk->ref_column}\")";
            
            // Add ON DELETE and ON UPDATE clauses if specified
            if ($fk->on_delete && $fk->on_delete !== 'NO ACTION') {
                $sql .= " ON DELETE {$fk->on_delete}";
            }
            if ($fk->on_update && $fk->on_update !== 'NO ACTION') {
                $sql .= " ON UPDATE {$fk->on_update}";
            }
        }
        
        $sql .= "\n)";
        
        return [
            'sql' => $sql,
            'description' => "Create table {$tableName}",
            'destructive' => false
        ];
    }
    
    private function generateAddColumn(array $op): array
    {
        $tableName = $op['table'];
        $column = ColumnDef::fromArray($op['column']);
        
        $sql = "ALTER TABLE \"{$tableName}\" ADD COLUMN " . $this->generateColumnDefinition($column, true);
        
        return [
            'sql' => $sql,
            'description' => "Add column {$column->name} to {$tableName}",
            'destructive' => false
        ];
    }
    
    private function generateModifyColumn(array $op): array
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
        $sql = "CREATE {$unique}INDEX \"{$index->name}\" ON \"{$tableName}\" (\"{$index->column}\")";
        
        return [
            'sql' => $sql,
            'description' => "Add {$index->type} index {$index->name} to {$tableName}",
            'destructive' => false
        ];
    }
    
    private function generateDropIndex(array $op): array
    {
        $indexName = $op['index_name'];
        
        $sql = "DROP INDEX \"{$indexName}\"";
        
        return [
            'sql' => $sql,
            'description' => "Drop index {$indexName}",
            'destructive' => true
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
            $sql = "ALTER TABLE \"{$tableName}\" DROP COLUMN \"{$columnName}\"";
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
                'sql' => "-- DROP COLUMN \"{$columnName}\" FROM \"{$tableName}\" (Not supported in SQLite < 3.35.0)",
                'destructive' => true,
                'warning' => 'Column drop not implemented for SQLite < 3.35.0. Manual table recreation required.'
            ]
        ];
    }
    
    private function getSqliteVersion(): string
    {
        return \SQLite3::version()['versionString'] ?? '3.0.0';
    }
    
    private function generateDropTable(array $op): array
    {
        $tableName = $op['table'];
        
        $sql = "DROP TABLE \"{$tableName}\"";
        
        return [
            'sql' => $sql,
            'description' => "Drop table {$tableName}",
            'destructive' => true
        ];
    }
    
    private function generateColumnDefinition(ColumnDef $column, bool $useColumnLevelPK = true): string
    {
        $sql = "\"{$column->name}\" {$column->type}";
        
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
                $sql .= " DEFAULT '{$column->default}'";
            }
        }
        
        return $sql;
    }
    
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
