<?php

namespace BaseApi\Models;

use BaseApi\App;
use BaseApi\Support\Uuid;
use BaseApi\Database\QueryBuilder;
use BaseApi\Database\ModelQuery;
use BaseApi\Database\Relations\BelongsTo;
use BaseApi\Database\Relations\HasMany;
use BaseApi\Cache\Cache;

abstract class BaseModel implements \JsonSerializable
{
    public string $id = '';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected static ?string $table = null;

    /** @var array<string, callable(ModelQuery<static>): void> Global scopes applied to all queries */
    protected static array $globalScopes = [];

    /** @var array Original row data for change detection and FK extraction */
    protected array $__row = [];

    /** @var array Cached loaded relations */
    protected array $__relationCache = [];

    /** @var array<string, string> Attribute casting definitions */
    protected array $casts = [];

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
        } else {
            $tableName .= 's';
        }

        return $tableName;
    }

    public static function find(string $id): ?static
    {
        return static::query()->where('id', '=', $id)->first();
    }

    /**
     * @return ModelQuery<static>
     */
    public static function where(string $column, string $operator, mixed $value): ModelQuery
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereEQ(string $column, mixed $value): ModelQuery
    {
        return static::where($column, '=', $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereNEQ(string $column, mixed $value): ModelQuery
    {
        return static::where($column, '!=', $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereLT(string $column, mixed $value): ModelQuery
    {
        return static::where($column, '<', $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereLTE(string $column, mixed $value): ModelQuery
    {
        return static::where($column, '<=', $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereGT(string $column, mixed $value): ModelQuery
    {
        return static::where($column, '>', $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereGTE(string $column, mixed $value): ModelQuery
    {
        return static::where($column, '>=', $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereLike(string $column, string $value): ModelQuery
    {
        return static::where($column, 'LIKE', $value);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereConditions(array $conditions): ModelQuery
    {
        $query = static::query();

        foreach ($conditions as $condition) {
            $query->where($condition['column'], $condition['operator'] ?? '=', $condition['value']);
        }

        return $query;
    }

    /**
     * @return ModelQuery<static>
     */
    public static function whereIn(string $column, array $values): ModelQuery
    {
        return static::query()->whereIn($column, $values);
    }

    /**
     * Start a query with eager loading relations
     * 
     * @param array $relations
     * @return ModelQuery<static>
     */
    public static function with(array $relations): ModelQuery
    {
        return static::query()->with($relations);
    }

    public static function firstWhere(string $column, string $operator, mixed $value): ?static
    {
        return static::where($column, $operator, $value)->first();
    }

    public static function firstWhereConditions(array $conditions): ?static
    {
        return static::whereConditions($conditions)->first();
    }

    public static function countWhere(string $column, string $operator, mixed $value): int
    {
        return static::where($column, $operator, $value)->qb()->count();
    }

    public static function countWhereConditions(array $conditions): int
    {
        return static::whereConditions($conditions)->qb()->count();
    }

    public static function exists(string $column, string $operator, mixed $value): bool
    {
        return static::where($column, $operator, $value)->first() !== null;
    }

    public static function existsConditions(array $conditions): bool
    {
        return static::whereConditions($conditions)->first() !== null;
    }

    public static function all(int $limit = 1000, int $offset = 0): array
    {
        return static::query()->limit($limit)->offset($offset)->get();
    }

    /**
     * Create an API query from request parameters (pagination, sorting, filtering, eager loading)
     */
    public static function apiQuery(\BaseApi\Http\Request $request, int $maxPerPage = 50): \BaseApi\Database\PaginatedResult
    {
        // Start with base query
        $query = static::with([]);

        // Parse eager loading from 'with' parameter
        $withParam = $request->query['with'] ?? '';
        if (!empty($withParam)) {
            $relations = array_filter(array_map('trim', explode(',', $withParam)));
            if (!empty($relations)) {
                $query = static::with($relations);
            }
        }

        // Apply pagination, sorting, and filtering
        [$query, $page, $perPage, $withTotal] = \BaseApi\Http\ControllerListHelpers::applyListParams(
            $query,
            $request,
            $maxPerPage
        );

        // Always include total count for API responses
        return $query->paginate($page, $perPage, $maxPerPage, true);
    }

    /**
     * Add a global scope that applies to all queries for this model
     * @param callable(ModelQuery<static>): void $scope
     */
    public static function addGlobalScope(string $name, callable $scope): void
    {
        static::$globalScopes[$name] = $scope;
    }

    /**
     * Remove a global scope
     */
    public static function removeGlobalScope(string $name): void
    {
        unset(static::$globalScopes[$name]);
    }

    /**
     * Create a new query with all global scopes applied
     * @return ModelQuery<static>
     */
    public static function query(): ModelQuery
    {
        $qb = App::db()->qb()->table(static::table());
        $modelQuery = new ModelQuery($qb, static::class);

        // Apply global scopes
        foreach (static::$globalScopes as $scope) {
            $scope($modelQuery);
        }

        return $modelQuery;
    }

    /**
     * Create a new instance safely
     * @return static
     */
    protected static function createInstance(): static
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Handle static method calls for scopes and query builder methods
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        // Check for scope methods first
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists(static::class, $scopeMethod)) {
            $query = static::query();
            $instance = static::createInstance();
            return $instance->$scopeMethod($query, ...$args);
        }

        // Delegate to query builder methods
        $query = static::query();
        if (method_exists($query, $method)) {
            return $query->$method(...$args);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on " . static::class);
    }

    public function save(): bool
    {
        $result = false;

        if (empty($this->id)) {
            $result = $this->insert();
        } else {
            $result = $this->update();
        }

        // Invalidate cache after successful save
        if ($result) {
            $this->invalidateCache();
        }

        return $result;
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

        $result = $affected > 0;

        // Invalidate cache after successful delete
        if ($result) {
            $this->invalidateCache();
        }

        return $result;
    }

    /**
     * Define a belongsTo relationship (many-to-one)
     */
    public function belongsTo(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null): BelongsTo
    {
        return new BelongsTo($this, $relatedClass, $foreignKey, $localKey);
    }

    /**
     * Define a hasMany relationship (one-to-many)
     */
    public function hasMany(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        return new HasMany($this, $relatedClass, $foreignKey, $localKey);
    }

    /**
     * Load a belongsTo relation (backward compatible - loads data directly)
     */
    public function loadBelongsTo(string $related, ?string $fk = null): ?BaseModel
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
     * Load a hasMany relation (backward compatible - loads data directly)
     */
    public function loadHasMany(string $related, ?string $fk = null): array
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
        $singularTable = static::singularize($currentTable);
        $fkColumn = $singularTable . '_id';

        return [$fkColumn, $relatedClass];
    }

    /**
     * Simple singularization with common irregular cases
     */
    public static function singularize(string $plural): string
    {
        // Handle common irregular cases
        $irregulars = [
            'categories' => 'category',
            'companies' => 'company',
            'countries' => 'country',
            'cities' => 'city',
            'people' => 'person',
            'children' => 'child',
        ];

        if (isset($irregulars[$plural])) {
            return $irregulars[$plural];
        }

        // Handle regular patterns
        if (str_ends_with($plural, 'ies')) {
            return substr($plural, 0, -3) . 'y';
        }

        if (str_ends_with($plural, 'ves')) {
            return substr($plural, 0, -3) . 'f';
        }

        if (str_ends_with($plural, 'ses') || str_ends_with($plural, 'shes') || str_ends_with($plural, 'ches')) {
            return substr($plural, 0, -2);
        }

        if (str_ends_with($plural, 's') && !str_ends_with($plural, 'ss')) {
            return substr($plural, 0, -1);
        }

        return $plural; // No change if can't singularize
    }

    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $data = [];
        foreach ($properties as $property) {
            // Skip static properties
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            // Skip uninitialized typed properties (including relationships)
            if ($property->hasType() && !$property->isInitialized($this)) {
                continue;
            }

            $data[$name] = $property->getValue($this);
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

                // Apply casting if defined
                $value = $instance->castAttribute($name, $value);

                // Simple type casting based on property type if no cast is defined
                if (!isset($instance->casts[$name])) {
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
                }

                $property->setValue($instance, $value);
            }
        }

        return $instance;
    }

    /**
     * Invalidate cache entries related to this model.
     */
    protected function invalidateCache(): void
    {
        $tableName = static::table();
        $modelClass = static::class;

        // Generate cache tags for this model
        $tags = [
            'model:' . $tableName,
            'model:' . $modelClass,
        ];

        // Add instance-specific tag if ID exists
        if (!empty($this->id)) {
            $tags[] = 'model:' . $tableName . ':' . $this->id;
            $tags[] = 'model:' . $modelClass . ':' . $this->id;
        }

        // Invalidate tagged cache entries
        foreach ($tags as $tag) {
            Cache::tags([$tag])->flush();
        }
    }

    /**
     * Get cache tags for this model instance.
     */
    public function getCacheTags(): array
    {
        $tableName = static::table();
        $modelClass = static::class;

        $tags = [
            'model:' . $tableName,
            'model:' . $modelClass,
        ];

        if (!empty($this->id)) {
            $tags[] = 'model:' . $tableName . ':' . $this->id;
            $tags[] = 'model:' . $modelClass . ':' . $this->id;
        }

        return $tags;
    }

    /**
     * Create a cached query for this model.
     * @return ModelQuery<static>
     */
    public static function cached(int $ttl = 300): ModelQuery
    {
        // Auto-tag with model information
        $tags = [
            'model:' . static::table(),
            'model:' . static::class,
        ];

        return static::query()->cacheWithTags($tags, $ttl);
    }

    /**
     * Load relations after model creation
     */
    public function load(array $relations): self
    {
        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                $this->$relation = $this->$relation()->get();
            }
        }
        return $this;
    }

    /**
     * Get a fresh instance of the model from database
     */
    public function fresh(array $with = []): ?static
    {
        if (empty($this->id)) {
            return null;
        }

        $query = static::query();
        if (!empty($with)) {
            $query = $query->with($with);
        }

        return $query->find($this->id);
    }

    /**
     * Cast an attribute to the appropriate type
     */
    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'array' => is_string($value) ? json_decode($value, true) : $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'date' => $value instanceof \DateTimeInterface ? $value : new \DateTime($value),
            default => $value,
        };
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
            // Skip static properties
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            // Skip relationship properties (BaseModel subclasses)
            if ($property->hasType()) {
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    if (is_subclass_of($typeName, BaseModel::class)) {
                        continue; // Skip relationship properties entirely
                    }
                }
            }

            // Skip uninitialized typed properties
            if ($property->hasType() && !$property->isInitialized($this)) {
                continue;
            }

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
            // Skip static properties
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            // Skip relationship properties (BaseModel subclasses)
            if ($property->hasType()) {
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    if (is_subclass_of($typeName, BaseModel::class)) {
                        continue; // Skip relationship properties entirely
                    }
                }
            }

            // Skip uninitialized typed properties
            if ($property->hasType() && !$property->isInitialized($this)) {
                continue;
            }

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
