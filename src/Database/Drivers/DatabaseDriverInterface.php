<?php

namespace BaseApi\Database\Drivers;

use PDO;
use BaseApi\Database\Migrations\MigrationPlan;

interface DatabaseDriverInterface
{
    /**
     * Create a PDO connection for this database type
     */
    public function createConnection(array $config): PDO;
    
    /**
     * Get the database name from the connection
     */
    public function getDatabaseName(PDO $pdo): string;
    
    /**
     * Get all tables in the database
     */
    public function getTables(PDO $pdo, string $dbName): array;
    
    /**
     * Get columns for a specific table
     */
    public function getColumns(PDO $pdo, string $dbName, string $tableName): array;
    
    /**
     * Get indexes for a specific table
     */
    public function getIndexes(PDO $pdo, string $dbName, string $tableName): array;
    
    /**
     * Get foreign keys for a specific table
     */
    public function getForeignKeys(PDO $pdo, string $dbName, string $tableName): array;
    
    /**
     * Generate SQL statements from migration plan
     */
    public function generateSql(MigrationPlan $plan): array;
    
    /**
     * Normalize a column type to standard format
     */
    public function normalizeColumnType(string $columnType): string;
    
    /**
     * Normalize a default value
     */
    public function normalizeDefault(?string $default, string $extra): ?string;
    
    /**
     * Map PHP type to SQL type for this database
     */
    public function phpTypeToSqlType(string $phpType, string $propertyName = ''): string;
    
    /**
     * Get the driver name
     */
    public function getName(): string;
}
