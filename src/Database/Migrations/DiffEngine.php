<?php

namespace BaseApi\Database\Migrations;

class DiffEngine
{
    /**
     * System tables that should never be dropped by migration generation.
     * These are typically framework/infrastructure tables.
     */
    private array $systemTables = [
        'jobs',           // Queue system jobs table
        'migrations',     // Migration tracking table  
        'schema_info',    // Schema version info
        'cache',          // Cache table
        'sessions',       // Session storage table
    ];
    
    /**
     * Add additional system table names that should be protected from dropping.
     */
    public function addSystemTable(string $tableName): void
    {
        if (!in_array($tableName, $this->systemTables, true)) {
            $this->systemTables[] = $tableName;
        }
    }
    
    /**
     * Add multiple system table names that should be protected from dropping.
     * 
     * @param string[] $tableNames
     */
    public function addSystemTables(array $tableNames): void
    {
        foreach ($tableNames as $tableName) {
            $this->addSystemTable($tableName);
        }
    }
    
    public function diff(ModelSchema $modelSchema, DatabaseSchema $dbSchema): MigrationPlan
    {
        $plan = new MigrationPlan();
        
        // Find tables to create, modify, or drop
        $modelTables = array_keys($modelSchema->tables);
        $dbTables = array_keys($dbSchema->tables);
        
        $tablesToCreate = array_diff($modelTables, $dbTables);
        $tablesToDrop = array_diff($dbTables, array_merge($modelTables, $this->systemTables));
        $tablesToCompare = array_intersect($modelTables, $dbTables);
        
        // Create new tables
        foreach ($tablesToCreate as $tableName) {
            $this->addCreateTableOp($plan, $modelSchema->tables[$tableName]);
        }
        
        // Compare existing tables
        foreach ($tablesToCompare as $tableName) {
            $this->diffTable($plan, $modelSchema->tables[$tableName], $dbSchema->tables[$tableName]);
        }
        
        // Drop removed tables (destructive)
        foreach ($tablesToDrop as $tableName) {
            $this->addDropTableOp($plan, $tableName);
        }
        
        return $plan;
    }

    private function addCreateTableOp(MigrationPlan $plan, TableDef $table): void
    {
        $plan->addOperation('create_table', [
            'table' => $table->name,
            'columns' => array_map(fn(ColumnDef $col): array => $col->toArray(), $table->columns),
            'destructive' => false
        ]);
        
        // Add indexes for the new table
        foreach ($table->indexes as $index) {
            // Include column type information for proper index generation
            $columnDef = $table->columns[$index->column] ?? null;
            $plan->addOperation('add_index', [
                'table' => $table->name,
                'index' => $index->toArray(),
                'column_type' => $columnDef?->type,
                'destructive' => false
            ]);
        }
        
        // Add foreign keys for the new table
        foreach ($table->fks as $fk) {
            $plan->addOperation('add_fk', [
                'table' => $table->name,
                'fk' => $fk->toArray(),
                'destructive' => false
            ]);
        }
    }

    private function addDropTableOp(MigrationPlan $plan, string $tableName): void
    {
        $plan->addOperation('drop_table', [
            'table' => $tableName,
            'destructive' => true
        ]);
    }

    private function diffTable(MigrationPlan $plan, TableDef $modelTable, TableDef $dbTable): void
    {
        // Diff columns
        $this->diffColumns($plan, $modelTable, $dbTable);
        
        // Diff indexes
        $this->diffIndexes($plan, $modelTable, $dbTable);
        
        // Diff foreign keys
        $this->diffForeignKeys($plan, $modelTable, $dbTable);
    }

    private function diffColumns(MigrationPlan $plan, TableDef $modelTable, TableDef $dbTable): void
    {
        $modelColumns = array_keys($modelTable->columns);
        $dbColumns = array_keys($dbTable->columns);
        
        $columnsToAdd = array_diff($modelColumns, $dbColumns);
        $columnsToDrop = array_diff($dbColumns, $modelColumns);
        $columnsToCompare = array_intersect($modelColumns, $dbColumns);
        
        // Add new columns
        foreach ($columnsToAdd as $columnName) {
            $plan->addOperation('add_column', [
                'table' => $modelTable->name,
                'column' => $modelTable->columns[$columnName]->toArray(),
                'destructive' => false
            ]);
        }
        
        // Compare existing columns
        foreach ($columnsToCompare as $columnName) {
            $modelColumn = $modelTable->columns[$columnName];
            $dbColumn = $dbTable->columns[$columnName];
            
            if ($this->columnsDiffer($modelColumn, $dbColumn)) {
                $destructive = $this->isColumnChangeDestructive($modelColumn, $dbColumn);
                
                $plan->addOperation('modify_column', [
                    'table' => $modelTable->name,
                    'column' => $modelColumn->toArray(),
                    'old_column' => $dbColumn->toArray(),
                    'destructive' => $destructive
                ]);
            }
        }
        
        // Drop removed columns (destructive)
        foreach ($columnsToDrop as $columnName) {
            $plan->addOperation('drop_column', [
                'table' => $modelTable->name,
                'column' => $columnName,
                'destructive' => true
            ]);
        }
    }

    private function diffIndexes(MigrationPlan $plan, TableDef $modelTable, TableDef $dbTable): void
    {
        $modelIndexes = array_keys($modelTable->indexes);
        $dbIndexes = array_keys($dbTable->indexes);
        
        $indexesToAdd = array_diff($modelIndexes, $dbIndexes);
        $indexesToDrop = array_diff($dbIndexes, $modelIndexes);
        $indexesToCompare = array_intersect($modelIndexes, $dbIndexes);
        
        // Add new indexes
        foreach ($indexesToAdd as $indexName) {
            $index = $modelTable->indexes[$indexName];
            // Include column type information for proper index generation
            $columnDef = $modelTable->columns[$index->column] ?? null;
            $plan->addOperation('add_index', [
                'table' => $modelTable->name,
                'index' => $index->toArray(),
                'column_type' => $columnDef?->type,
                'destructive' => false
            ]);
        }
        
        // Compare existing indexes
        foreach ($indexesToCompare as $indexName) {
            $modelIndex = $modelTable->indexes[$indexName];
            $dbIndex = $dbTable->indexes[$indexName];
            
            if ($this->indexesDiffer($modelIndex, $dbIndex)) {
                // Drop and recreate the index
                $plan->addOperation('drop_index', [
                    'table' => $modelTable->name,
                    'index' => $indexName,
                    'destructive' => false
                ]);
                
                // Include column type information for proper index generation
                $columnDef = $modelTable->columns[$modelIndex->column] ?? null;
                $plan->addOperation('add_index', [
                    'table' => $modelTable->name,
                    'index' => $modelIndex->toArray(),
                    'column_type' => $columnDef?->type,
                    'destructive' => false
                ]);
            }
        }
        
        // Drop removed indexes
        foreach ($indexesToDrop as $indexName) {
            $plan->addOperation('drop_index', [
                'table' => $modelTable->name,
                'index' => $indexName,
                'destructive' => false
            ]);
        }
    }

    private function diffForeignKeys(MigrationPlan $plan, TableDef $modelTable, TableDef $dbTable): void
    {
        $modelFks = array_keys($modelTable->fks);
        $dbFks = array_keys($dbTable->fks);
        
        $fksToAdd = array_diff($modelFks, $dbFks);
        $fksToDrop = array_diff($dbFks, $modelFks);
        $fksToCompare = array_intersect($modelFks, $dbFks);
        
        // Add new foreign keys
        foreach ($fksToAdd as $fkName) {
            $plan->addOperation('add_fk', [
                'table' => $modelTable->name,
                'fk' => $modelTable->fks[$fkName]->toArray(),
                'destructive' => false
            ]);
        }
        
        // Compare existing foreign keys
        foreach ($fksToCompare as $fkName) {
            $modelFk = $modelTable->fks[$fkName];
            $dbFk = $dbTable->fks[$fkName];
            
            if ($this->foreignKeysDiffer($modelFk, $dbFk)) {
                // Drop and recreate the foreign key
                $plan->addOperation('drop_fk', [
                    'table' => $modelTable->name,
                    'fk' => $fkName,
                    'destructive' => false
                ]);
                
                $plan->addOperation('add_fk', [
                    'table' => $modelTable->name,
                    'fk' => $modelFk->toArray(),
                    'destructive' => false
                ]);
            }
        }
        
        // Drop removed foreign keys
        foreach ($fksToDrop as $fkName) {
            $plan->addOperation('drop_fk', [
                'table' => $modelTable->name,
                'fk' => $fkName,
                'destructive' => false
            ]);
        }
    }

    private function columnsDiffer(ColumnDef $modelColumn, ColumnDef $dbColumn): bool
    {
        return $modelColumn->type !== $dbColumn->type ||
               $modelColumn->nullable !== $dbColumn->nullable ||
               $modelColumn->default !== $dbColumn->default ||
               $modelColumn->is_pk !== $dbColumn->is_pk;
    }

    private function isColumnChangeDestructive(ColumnDef $modelColumn, ColumnDef $dbColumn): bool
    {
        // Type changes that could lose data
        if ($this->isTypeShrinking($modelColumn->type, $dbColumn->type)) {
            return true;
        }

        // Making a column non-nullable when it was nullable
        return !$modelColumn->nullable && $dbColumn->nullable;
    }

    private function isTypeShrinking(string $newType, string $oldType): bool
    {
        // Simple heuristic for type shrinking
        // VARCHAR(100) -> VARCHAR(50) is shrinking
        if (preg_match('/VARCHAR\((\d+)\)/', $newType, $newMatches) && 
            preg_match('/VARCHAR\((\d+)\)/', $oldType, $oldMatches)) {
            return (int)$newMatches[1] < (int)$oldMatches[1];
        }
        
        // CHAR(36) -> CHAR(20) is shrinking
        if (preg_match('/CHAR\((\d+)\)/', $newType, $newMatches) && 
            preg_match('/CHAR\((\d+)\)/', $oldType, $oldMatches)) {
            return (int)$newMatches[1] < (int)$oldMatches[1];
        }
        
        // Different types entirely might be risky
        $baseNewType = preg_replace('/\([^)]*\)/', '', $newType);
        $baseOldType = preg_replace('/\([^)]*\)/', '', $oldType);
        
        if ($baseNewType !== $baseOldType) {
            // Some safe conversions
            $safeConversions = [
                'INT' => ['BIGINT'],
                'VARCHAR' => ['TEXT'],
                'DECIMAL' => ['FLOAT', 'DOUBLE'],
            ];
            return !(isset($safeConversions[$baseOldType]) && 
                in_array($baseNewType, $safeConversions[$baseOldType])); // Assume other type changes are risky
        }
        
        return false;
    }

    private function indexesDiffer(IndexDef $modelIndex, IndexDef $dbIndex): bool
    {
        return $modelIndex->column !== $dbIndex->column ||
               $modelIndex->type !== $dbIndex->type;
    }

    private function foreignKeysDiffer(ForeignKeyDef $modelFk, ForeignKeyDef $dbFk): bool
    {
        return $modelFk->column !== $dbFk->column ||
               $modelFk->ref_table !== $dbFk->ref_table ||
               $modelFk->ref_column !== $dbFk->ref_column ||
               $modelFk->on_delete !== $dbFk->on_delete ||
               $modelFk->on_update !== $dbFk->on_update;
    }
}
