<?php

namespace BaseApi\Database\Migrations;

class SqlGenerator
{
    public function generate(MigrationPlan $plan): array
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
            'destructive' => $op['destructive'] ?? false,
            'warning' => null
        ];
    }

    private function generateAddColumn(array $op): array
    {
        $tableName = $op['table'];
        $column = ColumnDef::fromArray($op['column']);
        
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN " . $this->generateColumnDefinition($column);
        
        return [
            'sql' => $sql,
            'destructive' => $op['destructive'] ?? false,
            'warning' => null
        ];
    }

    private function generateModifyColumn(array $op): array
    {
        $tableName = $op['table'];
        $column = ColumnDef::fromArray($op['column']);
        
        $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN " . $this->generateColumnDefinition($column);
        
        $warning = null;
        if ($op['destructive'] ?? false) {
            $warning = "Modifying column {$column->name} may cause data loss";
        }
        
        return [
            'sql' => $sql,
            'destructive' => $op['destructive'] ?? false,
            'warning' => $warning
        ];
    }

    private function generateAddIndex(array $op): array
    {
        $tableName = $op['table'];
        $index = IndexDef::fromArray($op['index']);
        
        if ($index->type === 'unique') {
            $sql = "ALTER TABLE `{$tableName}` ADD UNIQUE KEY `{$index->name}` (`{$index->column}`)";
        } else {
            $sql = "ALTER TABLE `{$tableName}` ADD INDEX `{$index->name}` (`{$index->column}`)";
        }
        
        return [
            'sql' => $sql,
            'destructive' => $op['destructive'] ?? false,
            'warning' => null
        ];
    }

    private function generateAddForeignKey(array $op): array
    {
        $tableName = $op['table'];
        $fk = ForeignKeyDef::fromArray($op['fk']);
        
        $sql = "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$fk->name}` " .
               "FOREIGN KEY (`{$fk->column}`) REFERENCES `{$fk->ref_table}` (`{$fk->ref_column}`) " .
               "ON DELETE {$fk->on_delete} ON UPDATE {$fk->on_update}";
        
        return [
            'sql' => $sql,
            'destructive' => $op['destructive'] ?? false,
            'warning' => null
        ];
    }

    private function generateDropForeignKey(array $op): array
    {
        $tableName = $op['table'];
        $fkName = $op['fk'];
        
        $sql = "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fkName}`";
        
        return [
            'sql' => $sql,
            'destructive' => $op['destructive'] ?? false,
            'warning' => null
        ];
    }

    private function generateDropIndex(array $op): array
    {
        $tableName = $op['table'];
        $indexName = $op['index'];
        
        $sql = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`";
        
        return [
            'sql' => $sql,
            'destructive' => $op['destructive'] ?? false,
            'warning' => null
        ];
    }

    private function generateDropColumn(array $op): array
    {
        $tableName = $op['table'];
        $columnName = $op['column'];
        
        $sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`";
        
        return [
            'sql' => $sql,
            'destructive' => true,
            'warning' => "Dropping column {$columnName} will permanently delete data"
        ];
    }

    private function generateDropTable(array $op): array
    {
        $tableName = $op['table'];
        
        $sql = "DROP TABLE `{$tableName}`";
        
        return [
            'sql' => $sql,
            'destructive' => true,
            'warning' => "Dropping table {$tableName} will permanently delete all data"
        ];
    }

    private function generateColumnDefinition(ColumnDef $column): string
    {
        $def = "`{$column->name}` {$column->type}";
        
        if (!$column->nullable) {
            $def .= " NOT NULL";
        } else {
            $def .= " NULL";
        }
        
        if ($column->default !== null) {
            if ($column->default === 'CURRENT_TIMESTAMP' || 
                $column->default === 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') {
                $def .= " DEFAULT {$column->default}";
            } else {
                $def .= " DEFAULT '{$column->default}'";
            }
        }
        
        return $def;
    }
}
