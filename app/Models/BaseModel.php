<?php

namespace BaseApi\Models;

use BaseApi\App;
use BaseApi\Support\Uuid;
use BaseApi\Database\QueryBuilder;
use BaseApi\Database\ModelQuery;

abstract class BaseModel implements \JsonSerializable
{
    public string $id = '';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected static ?string $table = null;

    /** @var array Original row data for change detection and FK extraction */
    protected array $__row = [];

    /** @var array Cached loaded relations */
    protected array $__relationCache = [];

    public static function table(): string
    {
        if (static::$table !== null) {
            return static::$table;
        }

        // Convert ClassName to table_name
        $className = (new \ReflectionClass(static::class))->getShortName();

        // Convert PascalCase to snake_case and pluralize
        $tableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));

        // Simple pluralization
        if (str_ends_with($tableName, 'y')) {
            $tableName = substr($tableName, 0, -1) . 'ies';
        } elseif (str_ends_with($tableName, 's') || str_ends_with($tableName, 'sh') || str_ends_with($tableName, 'ch')) {
            $tableName .= 'es';
        } else {
            $tableName .= 's';
        }

        return $tableName;
    }

    public static function find(string $id): ?static
    {
        $row = App::db()->qb()
            ->table(static::table())
            ->where('id', '=', $id)
            ->first();

        return $row ? static::fromRow($row) : null;
    }

    public static function where(string $column, string $operator, mixed $value): QueryBuilder
    {
        return App::db()->qb()
            ->table(static::table())
            ->where($column, $operator, $value);
    }

    public static function whereConditions(array $conditions): QueryBuilder
    {
        $qb = App::db()->qb()->table(static::table());

        foreach ($conditions as $condition) {
            $qb->where($condition['column'], $condition['operator'] ?? '=', $condition['value']);
        }

        return $qb;
    }

    public static function whereIn(string $column, array $values): QueryBuilder
    {
        return App::db()->qb()
            ->table(static::table())
            ->whereIn($column, $values);
    }

    /**
     * Start a query with eager loading relations
     * 
     * @param array $relations
     * @return ModelQuery<static>
     */
    public static function with(array $relations): ModelQuery
    {
        $qb = App::db()->qb()->table(static::table());
        $modelQuery = new ModelQuery($qb, static::class);
        return $modelQuery->with($relations);
    }

    public static function firstWhere(string $column, string $operator, mixed $value): ?static
    {
        $row = static::where($column, $operator, $value)->first();

        return $row ? static::fromRow($row) : null;
    }

    public static function firstWhereConditions(array $conditions): ?static
    {
        $row = static::whereConditions($conditions)->first();

        return $row ? static::fromRow($row) : null;
    }

    public static function all(int $limit = 1000, int $offset = 0): array
    {
        $rows = App::db()->qb()
            ->table(static::table())
            ->limit($limit)
            ->offset($offset)
            ->get();

        return array_map([static::class, 'fromRow'], $rows);
    }

    public function save(): bool
    {
        if (empty($this->id)) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    public function delete(): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $affected = App::db()->qb()
            ->table(static::table())
            ->where('id', '=', $this->id)
            ->delete();

        return $affected > 0;
    }

    /**
     * Load a belongsTo relation (many-to-one)
     */
    public function belongsTo(string $related, ?string $fk = null): ?BaseModel
    {
        // Check cache first
        if (isset($this->__relationCache[$related])) {
            return $this->__relationCache[$related];
        }

        [$fkColumn, $relatedTable, $relatedClass] = static::inferForeignKeyFromTypedProperty($related);

        if ($fk) {
            $fkColumn = $fk;
        }

        // Get FK value from current instance
        $fkValue = $this->__row[$fkColumn] ?? $this->$fkColumn ?? null;

        if (!$fkValue) {
            $this->__relationCache[$related] = null;
            return null;
        }

        // Load related model
        $relatedModel = $relatedClass::find($fkValue);
        $this->__relationCache[$related] = $relatedModel;

        return $relatedModel;
    }

    /**
     * Load a hasMany relation (one-to-many)
     */
    public function hasMany(string $related, ?string $fk = null): array
    {
        // Check cache first
        if (isset($this->__relationCache[$related])) {
            return $this->__relationCache[$related];
        }

        [$fkColumn, $relatedClass] = static::inferHasMany($related);

        if ($fk) {
            $fkColumn = $fk;
        }

        // Load related models where FK equals this model's ID
        $relatedModels = $relatedClass::where($fkColumn, '=', $this->id)->get();
        $this->__relationCache[$related] = $relatedModels;

        return $relatedModels;
    }

    /**
     * Get the type of relation for a given property name
     */
    public static function getRelationType(string $property): string
    {
        $reflection = new \ReflectionClass(static::class);

        if (!$reflection->hasProperty($property)) {
            throw new \InvalidArgumentException("Property {$property} not found on " . static::class);
        }

        $prop = $reflection->getProperty($property);
        $type = $prop->getType();

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            if (is_subclass_of($typeName, BaseModel::class)) {
                return 'belongsTo';
            }
        }

        // Check for array type hint in docblock
        $docComment = $prop->getDocComment();
        if ($docComment && preg_match('/@var\s+([^\s]+)\[\]/', $docComment, $matches)) {
            $elementType = $matches[1];
            if (class_exists($elementType) && is_subclass_of($elementType, BaseModel::class)) {
                return 'hasMany';
            }
        }

        throw new \InvalidArgumentException("Cannot determine relation type for property {$property}");
    }

    /**
     * Infer foreign key info from typed property for belongsTo relations
     * Returns [fkColumn, relatedTable, relatedClass]
     */
    public static function inferForeignKeyFromTypedProperty(string $prop): array
    {
        $reflection = new \ReflectionClass(static::class);

        if (!$reflection->hasProperty($prop)) {
            throw new \InvalidArgumentException("Property {$prop} not found on " . static::class);
        }

        $property = $reflection->getProperty($prop);
        $type = $property->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException("Property {$prop} must have a typed hint");
        }

        $relatedClass = $type->getName();
        if (!is_subclass_of($relatedClass, BaseModel::class)) {
            throw new \InvalidArgumentException("Property {$prop} must be a BaseModel subclass");
        }

        $fkColumn = $prop . '_id';
        $relatedTable = $relatedClass::table();

        return [$fkColumn, $relatedTable, $relatedClass];
    }

    /**
     * Infer hasMany relation info from property name and docblock
     * Returns [fkColumn, relatedClass]
     */
    public static function inferHasMany(string $prop): array
    {
        $reflection = new \ReflectionClass(static::class);

        if (!$reflection->hasProperty($prop)) {
            throw new \InvalidArgumentException("Property {$prop} not found on " . static::class);
        }

        $property = $reflection->getProperty($prop);
        $docComment = $property->getDocComment();

        if (!$docComment || !preg_match('/@var\s+([^\s]+)\[\]/', $docComment, $matches)) {
            throw new \InvalidArgumentException("Property {$prop} must have @var ClassName[] docblock for hasMany");
        }

        $relatedClass = $matches[1];
        if (!class_exists($relatedClass) || !is_subclass_of($relatedClass, BaseModel::class)) {
            throw new \InvalidArgumentException("Related class {$relatedClass} must be a BaseModel subclass");
        }

        // Generate FK column: singular of current table + _id
        $currentTable = static::table();
        $singularTable = rtrim($currentTable, 's'); // Simple singularization
        $fkColumn = $singularTable . '_id';

        return [$fkColumn, $relatedClass];
    }

    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $data = [];
        foreach ($properties as $property) {
            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromRow(array $row): static
    {
        /** @phpstan-ignore-next-line */
        $instance = new static();

        // Store original row data for relation loading and change detection
        $instance->__row = $row;

        $reflection = new \ReflectionClass($instance);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $row)) {
                $value = $row[$name];

                // Simple type casting based on property type
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $value = match ($type->getName()) {
                        'int' => (int) $value,
                        'float' => (float) $value,
                        'bool' => (bool) $value,
                        'string' => (string) $value,
                        default => $value,
                    };
                }

                $property->setValue($instance, $value);
            }
        }

        return $instance;
    }

    private function insert(): bool
    {
        // Generate UUID if not set
        if (empty($this->id)) {
            $this->id = Uuid::v7();
        }

        $data = $this->getInsertData();

        App::db()->qb()
            ->table(static::table())
            ->insert($data);

        return true;
    }

    private function update(): bool
    {
        $data = $this->getUpdateData();

        if (empty($data)) {
            return true; // Nothing to update
        }

        $affected = App::db()->qb()
            ->table(static::table())
            ->where('id', '=', $this->id)
            ->update($data);

        return $affected > 0;
    }

    private function getInsertData(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);

            // Skip timestamps for insert (let DB handle them)
            if (in_array($name, ['created_at', 'updated_at']) && $value === null) {
                continue;
            }

            if ($value !== null) {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    private function getUpdateData(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);

            // Skip id and created_at for updates
            if (in_array($name, ['id', 'created_at'])) {
                continue;
            }

            if ($value !== null) {
                $data[$name] = $value;
            }
        }

        return $data;
    }
}
