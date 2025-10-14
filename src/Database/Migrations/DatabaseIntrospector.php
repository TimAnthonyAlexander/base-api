<?php

namespace BaseApi\Database\Migrations;

use BaseApi\App;

class DatabaseIntrospector
{
    public function snapshot(): DatabaseSchema
    {
        $schema = new DatabaseSchema();
        $connection = App::db()->getConnection();
        $pdo = $connection->pdo();
        $driver = $connection->getDriver();
        $dbName = $driver->getDatabaseName($pdo);
        
        // Get all tables
        $tables = $driver->getTables($pdo, $dbName);
        
        foreach ($tables as $tableName) {
            $table = new TableDef($tableName);
            
            // Get columns
            $table->columns = $driver->getColumns($pdo, $dbName, $tableName);
            
            // Get indexes
            $table->indexes = $driver->getIndexes($pdo, $dbName, $tableName);
            
            // Get foreign keys
            $table->fks = $driver->getForeignKeys($pdo, $dbName, $tableName);
            
            $schema->tables[$tableName] = $table;
        }
        
        return $schema;
    }
}