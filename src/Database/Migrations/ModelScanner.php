<?php

namespace BaseApi\Database\Migrations;

use ReflectionClass;
use ReflectionProperty;
use BaseApi\Models\BaseModel;
use BaseApi\App;

class ModelScanner
{
    public function scan(string $modelsDir): ModelSchema
    {
        $schema = new ModelSchema();
        
        // Find all PHP files in models directory
        $files = glob($modelsDir . '/*.php');
        
        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);
            if (!$className || !class_exists($className)) {
                continue;
            }
            
            $reflection = new ReflectionClass($className);
            
            // Skip abstract classes and non-BaseModel classes
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(BaseModel::class)) {
                continue;
            }
            
            $table = $this->scanModel($reflection);
            $schema->tables[$table->name] = $table;
        }
        
        return $schema;
    }

    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        
        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        } else {
            return null;
        }
        
        // Extract class name
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
            return $namespace . '\\' . $className;
        }
        
        return null;
    }

    private function scanModel(ReflectionClass $reflection): TableDef
    {
        $className = $reflection->getName();
        
        // Get table name - check for static $table property, otherwise infer
        $tableName = $this->getTableName($reflection);
        
        $table = new TableDef($tableName);
        
        // Scan public typed properties
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            $type = $property->getType();
            if (!$type) {
                continue; // Skip untyped properties
            }
            
            $column = $this->propertyToColumn($property, $reflection);
            if ($column) {
                $table->columns[$column->name] = $column;
            }
            
            // Check if this is a foreign key (typed as another model)
            $fk = $this->propertyToForeignKey($property, $reflection);
            if ($fk) {
                $table->fks[$fk->name] = $fk;
                
                // Add the FK column to the table
                $fkColumn = new ColumnDef($fk->column, 'CHAR(36)', $type->allowsNull());
                $table->columns[$fk->column] = $fkColumn;
            }
        }
        
        // Add indexes from static $indexes property
        $this->addIndexes($table, $reflection);
        
        // Apply column overrides from static $columns property
        $this->applyColumnOverrides($table, $reflection);
        
        return $table;
    }

    private function getTableName(ReflectionClass $reflection): string
    {
        // Check for static $table property
        if ($reflection->hasProperty('table')) {
            $tableProperty = $reflection->getProperty('table');
            if ($tableProperty->isStatic()) {
                $tableProperty->setAccessible(true);
                $tableName = $tableProperty->getValue();
                if ($tableName) {
                    return $tableName;
                }
            }
        }
        
        // Infer from class name: UserPost -> user_posts
        $className = $reflection->getShortName();
        return $this->classNameToTableName($className);
    }

    private function classNameToTableName(string $className): string
    {
        // Convert PascalCase to snake_case and pluralize
        $snakeCase = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($className)));
        
        // Simple pluralization (just add 's' for now)
        if (!str_ends_with($snakeCase, 's')) {
            $snakeCase .= 's';
        }
        
        return $snakeCase;
    }

    private function propertyToColumn(ReflectionProperty $property, ReflectionClass $reflection): ?ColumnDef
    {
        $name = $property->getName();
        $type = $property->getType();
        
        if (!$type) {
            return null;
        }
        
        // Skip model-typed properties (they become FKs, not direct columns)
        if ($this->isModelType($type)) {
            return null;
        }
        
        $nullable = $type->allowsNull();
        $sqlType = $this->phpTypeToSqlType($type, $name);
        $isPk = $name === 'id';
        $default = null;
        
        // Special handling for timestamps
        if (in_array($name, ['created_at', 'updated_at'])) {
            $sqlType = 'DATETIME';
            if ($name === 'created_at') {
                $default = 'CURRENT_TIMESTAMP';
            } elseif ($name === 'updated_at') {
                $default = 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            }
        }
        
        return new ColumnDef($name, $sqlType, $nullable, $default, $isPk);
    }

    private function propertyToForeignKey(ReflectionProperty $property, ReflectionClass $reflection): ?ForeignKeyDef
    {
        $type = $property->getType();
        
        if (!$this->isModelType($type)) {
            return null;
        }
        
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }
        
        $modelClass = $type->getName();
        $propertyName = $property->getName();
        
        // Create FK column name: project -> project_id
        $fkColumnName = $propertyName . '_id';
        
        // Get referenced table name
        $modelReflection = new ReflectionClass($modelClass);
        $refTableName = $this->getTableName($modelReflection);
        
        // Create FK constraint name
        $fkName = 'fk_' . $this->getTableName($reflection) . '_' . $fkColumnName;
        
        // Add the FK column to the table
        $fkColumn = new ColumnDef($fkColumnName, 'CHAR(36)', $type->allowsNull());
        
        return new ForeignKeyDef($fkName, $fkColumnName, $refTableName, 'id');
    }

    private function isModelType(\ReflectionType $type): bool
    {
        if (!$type instanceof \ReflectionNamedType) {
            return false;
        }
        
        $typeName = $type->getName();
        
        // Check if the type is a class that extends BaseModel
        if (class_exists($typeName)) {
            $typeReflection = new ReflectionClass($typeName);
            return $typeReflection->isSubclassOf(BaseModel::class);
        }
        
        return false;
    }

    private function phpTypeToSqlType(\ReflectionType $type, string $propertyName = ''): string
    {
        if (!$type instanceof \ReflectionNamedType) {
            $connection = App::db()->getConnection();
            $driver = $connection->getDriver();
            return $driver->phpTypeToSqlType('string', $propertyName);
        }
        
        $typeName = $type->getName();
        $connection = App::db()->getConnection();
        $driver = $connection->getDriver();
        
        return $driver->phpTypeToSqlType($typeName, $propertyName);
    }

    private function addIndexes(TableDef $table, ReflectionClass $reflection): void
    {
        if (!$reflection->hasProperty('indexes')) {
            return;
        }
        
        $indexesProperty = $reflection->getProperty('indexes');
        if (!$indexesProperty->isStatic()) {
            return;
        }
        
        $indexesProperty->setAccessible(true);
        $indexes = $indexesProperty->getValue();
        
        if (!is_array($indexes)) {
            return;
        }
        
        foreach ($indexes as $column => $type) {
            $indexName = ($type === 'unique' ? 'uniq_' : 'idx_') . $table->name . '_' . $column;
            $table->indexes[$indexName] = new IndexDef($indexName, $column, $type);
        }
    }

    private function applyColumnOverrides(TableDef $table, ReflectionClass $reflection): void
    {
        if (!$reflection->hasProperty('columns')) {
            return;
        }
        
        $columnsProperty = $reflection->getProperty('columns');
        if (!$columnsProperty->isStatic()) {
            return;
        }
        
        $columnsProperty->setAccessible(true);
        $columnOverrides = $columnsProperty->getValue();
        
        if (!is_array($columnOverrides)) {
            return;
        }
        
        foreach ($columnOverrides as $columnName => $override) {
            if (!isset($table->columns[$columnName])) {
                continue;
            }
            
            $column = $table->columns[$columnName];
            
            if (isset($override['type'])) {
                $column->type = $override['type'];
            }
            
            if (isset($override['null'])) {
                $column->nullable = $override['null'];
            }
            
            if (isset($override['default'])) {
                $column->default = $override['default'];
            }
        }
    }
}
