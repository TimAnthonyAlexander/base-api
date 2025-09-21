<?php

namespace BaseApi\Database;

use InvalidArgumentException;
use ReflectionClass;
use BaseApi\Models\BaseModel;
use BaseApi\Cache\Cache;

/**
 * @template T of BaseModel
 */
class ModelQuery
{
    private array $eagerRelations = [];

    private ?string $cacheKey = null;

    private ?int $cacheTtl = null;

    private array $cacheTags = [];

    private bool $cacheEnabled = true;

    /**
     * @param class-string<T> $modelClass
     */
    public function __construct(private readonly QueryBuilder $qb, private readonly string $modelClass)
    {
    }

    /**
     * Add a WHERE clause
     * @return ModelQuery<T>
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->qb->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add WHERE IN clause
     * @return ModelQuery<T>
     */
    public function whereIn(string $column, array $values): self
    {
        $this->qb->whereIn($column, $values);
        return $this;
    }

    /**
     * Add OR WHERE clause
     */
    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->qb->orWhere($column, $operator, $value);
        return $this;
    }

    /**
     * Add WHERE IS NULL clause
     */
    public function whereNull(string $column): self
    {
        $this->qb->whereNull($column);
        return $this;
    }

    /**
     * Add WHERE IS NOT NULL clause
     */
    public function whereNotNull(string $column): self
    {
        $this->qb->whereNotNull($column);
        return $this;
    }

    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->qb->orderBy($column, $direction);
        return $this;
    }

    /**
     * Set LIMIT
     */
    public function limit(int $count): self
    {
        $this->qb->limit($count);
        return $this;
    }

    /**
     * Set OFFSET  
     */
    public function offset(int $count): self
    {
        $this->qb->offset($count);
        return $this;
    }

    /**
     * Specify relations to eager load
     */
    public function with(array $relations): self
    {
        // Limit to 5 relations as per spec
        if (count($relations) > 5) {
            throw new InvalidArgumentException("Maximum 5 relations allowed in with()");
        }

        $this->eagerRelations = array_merge($this->eagerRelations, $relations);
        return $this;
    }

    /**
     * Paginate results
     */
    public function paginate(int $page, int $perPage, ?int $maxPerPage = null, bool $withTotal = false): PaginatedResult
    {
        if ($maxPerPage && $perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        $result = $this->qb->paginate($page, $perPage, $withTotal);

        // Hydrate the data into model instances
        $models = $this->hydrateRows($result->data);

        // Apply eager loading if specified
        if ($this->eagerRelations !== []) {
            $models = $this->applyEagerLoading($models);
        }

        return new PaginatedResult($models, $result->page, $result->perPage, $result->total);
    }

    /**
     * Get all results as hydrated models
     * 
     * @return T[]
     */
    public function get(): array
    {
        // Check cache first if enabled
        if ($this->cacheEnabled) {
            $cacheKey = $this->getCacheKey();

            $cache = $this->cacheTags === [] ? Cache::driver() : Cache::tags($this->cacheTags);

            $ttl = $this->cacheTtl ?? $this->getDefaultCacheTtl();

            return $cache->remember($cacheKey, $ttl, fn(): array => $this->executeGet());
        }

        return $this->executeGet();
    }

    /**
     * Get first result as hydrated model
     * 
     * @return T|null
     */
    public function first(): ?BaseModel
    {
        // Check cache first if enabled
        if ($this->cacheEnabled) {
            $cacheKey = $this->getCacheKey() . ':first';

            $cache = $this->cacheTags === [] ? Cache::driver() : Cache::tags($this->cacheTags);

            $ttl = $this->cacheTtl ?? $this->getDefaultCacheTtl();

            return $cache->remember($cacheKey, $ttl, fn(): ?BaseModel => $this->executeFirst());
        }

        return $this->executeFirst();
    }

    /**
     * Get access to underlying QueryBuilder
     */
    public function qb(): QueryBuilder
    {
        return $this->qb;
    }

    /**
     * Cache query results with optional TTL and custom key.
     */
    public function cache(?int $ttl = null, ?string $key = null): self
    {
        $this->cacheTtl = $ttl;
        $this->cacheKey = $key;
        $this->cacheEnabled = true;

        return $this;
    }

    /**
     * Cache query results with tags for invalidation.
     */
    public function cacheWithTags(array $tags, int $ttl = 300): self
    {
        $this->cacheTags = $tags;
        $this->cacheTtl = $ttl;
        $this->cacheEnabled = true;

        return $this;
    }

    /**
     * Disable caching for this query.
     */
    public function noCache(): self
    {
        $this->cacheEnabled = false;
        return $this;
    }

    /**
     * Get cache key for current query.
     */
    public function getCacheKey(): string
    {
        if ($this->cacheKey) {
            return $this->cacheKey;
        }

        // Generate cache key from query components
        $sqlData = $this->qb->toSql();
        $components = [
            'model' => $this->modelClass,
            'sql' => $sqlData['sql'],
            'bindings' => $sqlData['bindings'],
            'relations' => $this->eagerRelations,
        ];

        return 'query:' . hash('md5', serialize($components));
    }

    /**
     * Execute the get query without caching.
     * 
     * @return T[]
     */
    private function executeGet(): array
    {
        $rows = $this->qb->get();
        $models = $this->hydrateRows($rows);

        // Apply eager loading if specified
        if ($this->eagerRelations !== []) {
            return $this->applyEagerLoading($models);
        }

        return $models;
    }

    /**
     * Execute the first query without caching.
     * 
     * @return T|null
     */
    private function executeFirst(): ?BaseModel
    {
        $row = $this->qb->first();
        if (!$row) {
            return null;
        }

        $model = $this->hydrateRow($row);

        // Apply eager loading if specified
        if ($this->eagerRelations !== []) {
            $models = $this->applyEagerLoading([$model]);
            return $models[0] ?? null;
        }

        return $model;
    }

    /**
     * Get default cache TTL from configuration.
     */
    private function getDefaultCacheTtl(): int
    {
        // Default to 5 minutes for model queries
        return 300;
    }

    /**
     * Hydrate array of rows into model instances
     *
     * @return T[]
     */
    private function hydrateRows(array $rows): array
    {
        return array_map(fn($row): BaseModel => $this->hydrateRow($row), $rows);
    }

    /**
     * Hydrate single row into model instance
     *
     * @return T
     */
    private function hydrateRow(array $row): BaseModel
    {
        $model = $this->modelClass::fromRow($row);

        // Store the original row data for relation loading
        $reflection = new ReflectionClass($model);
        if ($reflection->hasProperty('__row')) {
            $rowProperty = $reflection->getProperty('__row');
            $rowProperty->setAccessible(true);
            $rowProperty->setValue($model, $row);
        }

        return $model;
    }

    /**
     * Apply eager loading to collection of models
     * 
     * @param T[] $models
     * @return T[]
     */
    private function applyEagerLoading(array $models): array
    {
        if ($models === [] || $this->eagerRelations === []) {
            return $models;
        }

        foreach ($this->eagerRelations as $relation) {
            $this->loadRelation($models, $relation);
        }

        return $models;
    }

    /**
     * Load a specific relation for a collection of models
     *
     * @param T[] $models
     */
    private function loadRelation(array $models, string $relation): void
    {
        if ($models === []) {
            return;
        }

        $modelClass = $this->modelClass;
        $relationType = $modelClass::getRelationType($relation);

        if ($relationType === 'belongsTo') {
            $this->loadBelongsTo($models, $relation);
        } elseif ($relationType === 'hasMany') {
            $this->loadHasMany($models, $relation);
        }
    }

    /**
     * Load belongsTo relations with a single query
     *
     * @param T[] $models
     */
    private function loadBelongsTo(array $models, string $relation): void
    {
        $modelClass = $this->modelClass;
        [$fkColumn, $relatedTable, $relatedClass] = $modelClass::inferForeignKeyFromTypedProperty($relation);

        // Collect all foreign key values
        $fkValues = [];
        foreach ($models as $model) {
            $reflection = new ReflectionClass($model);
            if ($reflection->hasProperty('__row')) {
                $rowProperty = $reflection->getProperty('__row');
                $rowProperty->setAccessible(true);
                $row = $rowProperty->getValue($model);

                if (isset($row[$fkColumn]) && $row[$fkColumn] !== null) {
                    $fkValues[] = $row[$fkColumn];
                }
            }
        }

        if ($fkValues === []) {
            return;
        }

        // Load related models in one query
        $relatedModels = $relatedClass::whereIn('id', array_unique($fkValues))->get();

        // Index by ID for quick lookup
        $relatedByID = [];
        foreach ($relatedModels as $related) {
            $relatedByID[$related->id] = $related;
        }

        // Assign to each model
        foreach ($models as $model) {
            $reflection = new ReflectionClass($model);
            if ($reflection->hasProperty('__row')) {
                $rowProperty = $reflection->getProperty('__row');
                $rowProperty->setAccessible(true);
                $row = $rowProperty->getValue($model);

                $fkValue = $row[$fkColumn] ?? null;
                // Set the relation property on the model
                if ($fkValue && isset($relatedByID[$fkValue]) && $reflection->hasProperty($relation)) {
                    $relationProperty = $reflection->getProperty($relation);
                    $relationProperty->setAccessible(true);
                    $relationProperty->setValue($model, $relatedByID[$fkValue]);
                }
            }
        }
    }

    /**
     * Load hasMany relations with a single query
     *
     * @param T[] $models
     */
    private function loadHasMany(array $models, string $relation): void
    {
        $modelClass = $this->modelClass;
        [$fkColumn, $relatedClass] = $modelClass::inferHasMany($relation);

        // Collect all IDs from parent models
        $parentIds = array_map(fn($model): string => $model->id, $models);

        if ($parentIds === []) {
            return;
        }

        // Load related models in one query
        $relatedModels = $relatedClass::whereIn($fkColumn, $parentIds)->get();

        // Group by foreign key
        $relatedByFK = [];
        foreach ($relatedModels as $related) {
            $fkValue = $related->$fkColumn;
            if (!isset($relatedByFK[$fkValue])) {
                $relatedByFK[$fkValue] = [];
            }

            $relatedByFK[$fkValue][] = $related;
        }

        // Assign arrays to each model
        foreach ($models as $model) {
            $relatedArray = $relatedByFK[$model->id] ?? [];

            // Set the relation property on the model
            $reflection = new ReflectionClass($model);
            if ($reflection->hasProperty($relation)) {
                $relationProperty = $reflection->getProperty($relation);
                $relationProperty->setAccessible(true);
                $relationProperty->setValue($model, $relatedArray);
            }
        }
    }
}
