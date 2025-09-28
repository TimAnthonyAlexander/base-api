<?php

namespace BaseApi\Database\Migrations;

use Exception;
use ReflectionNamedType;
use ReflectionType;
use ReflectionClass;
use ReflectionProperty;
use BaseApi\Models\BaseModel;
use BaseApi\App;
use ReflectionException;

class ModelScanner
{
    public function scan(string $modelsDir): ModelSchema
    {
        $schema = new ModelSchema();

        // Find all PHP files in models directory
        $files = glob($modelsDir . '/*.php');

        foreach ($files as $file) {
            // Skip if file no longer exists (might have been deleted)
            if (!file_exists($file)) {
                continue;
            }
            
            $className = $this->getClassNameFromFile($file);
            if (!$className) {
                continue;
            }
            
            // Try to load the class, but handle errors gracefully
            try {
                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new ReflectionClass($className);
                // Skip abstract classes and non-BaseModel classes
                if ($reflection->isAbstract()) {
                    continue;
                }

                if (!$reflection->isSubclassOf(BaseModel::class)) {
                    continue;
                }

                $table = $this->scanModel($reflection);
                $schema->tables[$table->name] = $table;
                
            } catch (Exception) {
                // Skip files that can't be loaded (deleted, syntax errors, etc.)
                continue;
            }
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

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function scanModel(ReflectionClass $reflection): TableDef
    {

        // Get table name - check for static $table property, otherwise infer
        $tableName = $this->getTableName($reflection);

        $table = new TableDef($tableName);

        // Scan public typed properties (excluding static properties)
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $type = $property->getType();
            if (!$type) {
                continue; // Skip untyped properties
            }

            // Skip static properties (like $indexes, $table, etc.)
            if ($property->isStatic()) {
                continue;
            }

            $column = $this->propertyToColumn($property);
            if ($column instanceof ColumnDef) {
                $table->columns[$column->name] = $column;
            }

            // Check if this is a foreign key (typed as another model)
            $fk = $this->propertyToForeignKey($property, $reflection);
            if ($fk instanceof ForeignKeyDef) {
                $table->fks[$fk->name] = $fk;

                // Add the FK column to the table
                $fkColumn = new ColumnDef($fk->column, 'CHAR(36)', $type->allowsNull());
                $table->columns[$fk->column] = $fkColumn;
            }

            // Also check if this is a foreign key based on _id naming convention
            $idFk = $this->idPropertyToForeignKey($property, $reflection);
            if ($idFk instanceof ForeignKeyDef) {
                $table->fks[$idFk->name] = $idFk;
            }
        }

        // Add indexes from static $indexes property
        $this->addIndexes($table, $reflection);

        // Apply column overrides from static $columns property
        $this->applyColumnOverrides($table, $reflection);

        return $table;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
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

        // Infer from class name: UserPost -> user_post
        $className = $reflection->getShortName();
        return $this->classNameToTableName($className);
    }

    private function classNameToTableName(string $className): string
    {
        // Convert PascalCase to snake_case (no pluralization)
        return strtolower((string) preg_replace('/([A-Z])/', '_$1', lcfirst($className)));
    }

    private function snakeCaseToPascalCase(string $snakeCase): string
    {
        // Convert snake_case to PascalCase: hotel_room -> HotelRoom
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $snakeCase)));
    }

    private function findModelClassInNamespace(string $namespace, string $modelName): ?string
    {
        // Get all declared classes and find ones in the namespace that end with the model name
        $declaredClasses = get_declared_classes();
        $modelNameLower = strtolower($modelName);

        foreach ($declaredClasses as $className) {
            // Check if class is in the target namespace
            if (str_starts_with($className, $namespace . '\\')) {
                $shortName = substr($className, strlen($namespace) + 1);

                // Check if class name ends with the model name (case insensitive)
                if (str_ends_with(strtolower($shortName), $modelNameLower)) {
                    // Verify it's a BaseModel
                    try {
                        $reflection = new ReflectionClass($className);
                        if ($reflection->isSubclassOf(BaseModel::class)) {
                            return $className;
                        }
                    } catch (ReflectionException) {
                        continue;
                    }
                }
            }
        }

        return null;
    }

    private function propertyToColumn(ReflectionProperty $property): ?ColumnDef
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

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function propertyToForeignKey(ReflectionProperty $property, ReflectionClass $reflection): ?ForeignKeyDef
    {
        $type = $property->getType();

        if (!$this->isModelType($type)) {
            return null;
        }

        if (!$type instanceof ReflectionNamedType) {
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
        new ColumnDef($fkColumnName, 'CHAR(36)', $type->allowsNull());

        return new ForeignKeyDef($fkName, $fkColumnName, $refTableName, 'id');
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function idPropertyToForeignKey(ReflectionProperty $property, ReflectionClass $reflection): ?ForeignKeyDef
    {
        $propertyName = $property->getName();

        // Only process properties ending with _id
        if (!str_ends_with($propertyName, '_id')) {
            return null;
        }

        // Skip if this property is already handled as a model-typed FK
        $type = $property->getType();
        if ($this->isModelType($type)) {
            return null;
        }

        // Extract model name from property name: hotel_id -> hotel -> Hotel
        $modelName = substr($propertyName, 0, -3); // Remove '_id'
        $modelClassName = $this->snakeCaseToPascalCase($modelName); // hotel -> Hotel

        // Try to find the model class in the same namespace
        $currentNamespace = $reflection->getNamespaceName();
        $fullModelClassName = $currentNamespace . '\\' . $modelClassName;

        // If the direct match doesn't exist, try to find a class that ends with the model name
        if (!class_exists($fullModelClassName)) {
            $fullModelClassName = $this->findModelClassInNamespace($currentNamespace, $modelName);
        }

        // Check if the model class exists and is a BaseModel
        if (!$fullModelClassName || !class_exists($fullModelClassName)) {
            return null;
        }

        $modelReflection = new ReflectionClass($fullModelClassName);
        if (!$modelReflection->isSubclassOf(BaseModel::class)) {
            return null;
        }

        // Get referenced table name
        $refTableName = $this->getTableName($modelReflection);

        // Create FK constraint name
        $fkName = 'fk_' . $this->getTableName($reflection) . '_' . $propertyName;

        return new ForeignKeyDef($fkName, $propertyName, $refTableName, 'id');
    }

    private function isModelType(ReflectionType $type): bool
    {
        if (!$type instanceof ReflectionNamedType) {
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

    private function phpTypeToSqlType(ReflectionType $type, string $propertyName = ''): string
    {
        if (!$type instanceof ReflectionNamedType) {
            $connection = App::db()->getConnection();
            $driver = $connection->getDriver();
            return $driver->phpTypeToSqlType('string', $propertyName);
        }

        $typeName = $type->getName();
        $connection = App::db()->getConnection();
        $driver = $connection->getDriver();

        return $driver->phpTypeToSqlType($typeName, $propertyName);
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
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

    /**
     * @param ReflectionClass<object> $reflection
     */
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
