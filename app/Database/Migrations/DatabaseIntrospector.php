<?php

namespace BaseApi\Database\Migrations;

use BaseApi\App;
use PDO;

class DatabaseIntrospector
{
    public function snapshot(): DatabaseSchema
    {
        $schema = new DatabaseSchema();
        $pdo = App::db()->pdo();
        $dbName = $this->getDatabaseName($pdo);
        
        // Get all tables
        $tables = $this->getTables($pdo, $dbName);
        
        foreach ($tables as $tableName) {
            $table = new TableDef($tableName);
            
            // Get columns
            $table->columns = $this->getColumns($pdo, $dbName, $tableName);
            
            // Get indexes
            $table->indexes = $this->getIndexes($pdo, $dbName, $tableName);
            
            // Get foreign keys
            $table->fks = $this->getForeignKeys($pdo, $dbName, $tableName);
            
            $schema->tables[$tableName] = $table;
        }
        
        return $schema;
    }

    private function getDatabaseName(PDO $pdo): string
    {
        $stmt = $pdo->query('SELECT DATABASE()');
        return $stmt->fetchColumn();
    }

    private function getTables(PDO $pdo, string $dbName): array
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

    private function getColumns(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                COLUMN_KEY,
                EXTRA
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $stmt->execute([$dbName, $tableName]);
        
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columnName = $row['COLUMN_NAME'];
            $type = $this->normalizeColumnType($row['COLUMN_TYPE']);
            $nullable = $row['IS_NULLABLE'] === 'YES';
            $default = $this->normalizeDefault($row['COLUMN_DEFAULT'], $row['EXTRA']);
            $isPk = $row['COLUMN_KEY'] === 'PRI';
            
            $columns[$columnName] = new ColumnDef($columnName, $type, $nullable, $default, $isPk);
        }
        
        return $columns;
    }

    private function getIndexes(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                INDEX_NAME,
                COLUMN_NAME,
                NON_UNIQUE
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ?
            AND INDEX_NAME != 'PRIMARY'
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ");
        $stmt->execute([$dbName, $tableName]);
        
        $indexes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $indexName = $row['INDEX_NAME'];
            $columnName = $row['COLUMN_NAME'];
            $type = $row['NON_UNIQUE'] == 0 ? 'unique' : 'index';
            
            // For simplicity, we only handle single-column indexes for now
            if (!isset($indexes[$indexName])) {
                $indexes[$indexName] = new IndexDef($indexName, $columnName, $type);
            }
        }
        
        return $indexes;
    }

    private function getForeignKeys(PDO $pdo, string $dbName, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                DELETE_RULE,
                UPDATE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc 
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ? 
            AND kcu.TABLE_NAME = ?
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$dbName, $tableName]);
        
        $fks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fkName = $row['CONSTRAINT_NAME'];
            $column = $row['COLUMN_NAME'];
            $refTable = $row['REFERENCED_TABLE_NAME'];
            $refColumn = $row['REFERENCED_COLUMN_NAME'];
            $onDelete = $row['DELETE_RULE'];
            $onUpdate = $row['UPDATE_RULE'];
            
            $fks[$fkName] = new ForeignKeyDef($fkName, $column, $refTable, $refColumn, $onDelete, $onUpdate);
        }
        
        return $fks;
    }

    private function normalizeColumnType(string $columnType): string
    {
        // Convert MySQL types to our normalized format
        $columnType = strtoupper($columnType);
        
        // Handle common type mappings
        if (str_starts_with($columnType, 'VARCHAR')) {
            return $columnType; // Keep as-is: VARCHAR(255)
        }
        
        if (str_starts_with($columnType, 'CHAR')) {
            return $columnType; // Keep as-is: CHAR(36)
        }
        
        if (str_starts_with($columnType, 'DECIMAL')) {
            return $columnType; // Keep as-is: DECIMAL(18,6)
        }
        
        // Normalize boolean types
        if ($columnType === 'TINYINT(1)') {
            return 'BOOLEAN';
        }
        
        // Handle other common types
        return match (true) {
            str_starts_with($columnType, 'INT') => 'INT',
            str_starts_with($columnType, 'BIGINT') => 'BIGINT',
            str_starts_with($columnType, 'DATETIME') => 'DATETIME',
            str_starts_with($columnType, 'TIMESTAMP') => 'DATETIME',
            str_starts_with($columnType, 'TEXT') => 'TEXT',
            str_starts_with($columnType, 'JSON') => 'JSON',
            default => $columnType
        };
    }

    private function normalizeDefault(?string $default, string $extra): ?string
    {
        if ($default === null) {
            // Check for auto-generated defaults in EXTRA column
            if (str_contains($extra, 'DEFAULT_GENERATED')) {
                if (str_contains($extra, 'CURRENT_TIMESTAMP')) {
                    if (str_contains($extra, 'on update CURRENT_TIMESTAMP')) {
                        return 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
                    }
                    return 'CURRENT_TIMESTAMP';
                }
            }
            return null;
        }
        
        // Handle special MySQL defaults
        if ($default === 'CURRENT_TIMESTAMP') {
            if (str_contains($extra, 'on update CURRENT_TIMESTAMP')) {
                return 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            }
            return 'CURRENT_TIMESTAMP';
        }
        
        return $default;
    }
}
