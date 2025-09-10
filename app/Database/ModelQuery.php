<?php

namespace BaseApi\Database;

use BaseApi\Models\BaseModel;

/**
 * @template T of BaseModel
 */
class ModelQuery
{
    private QueryBuilder $qb;
    private string $modelClass;
    private array $eagerRelations = [];

    /**
     * @param class-string<T> $modelClass
     */
    public function __construct(QueryBuilder $qb, string $modelClass)
    {
        $this->qb = $qb;
        $this->modelClass = $modelClass;
    }

    /**
     * Add a WHERE clause
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->qb->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add WHERE IN clause
     */
    public function whereIn(string $column, array $values): self
    {
        $this->qb->whereIn($column, $values);
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
            throw new \InvalidArgumentException("Maximum 5 relations allowed in with()");
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
        if (!empty($this->eagerRelations)) {
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
        $rows = $this->qb->get();
        $models = $this->hydrateRows($rows);
        
        // Apply eager loading if specified
        if (!empty($this->eagerRelations)) {
            $models = $this->applyEagerLoading($models);
        }
        
        return $models;
    }

    /**
     * Get first result as hydrated model
     * 
     * @return T|null
     */
    public function first(): ?BaseModel
    {
        $row = $this->qb->first();
        if (!$row) {
            return null;
        }
        
        $model = $this->hydrateRow($row);
        
        // Apply eager loading if specified
        if (!empty($this->eagerRelations)) {
            $models = $this->applyEagerLoading([$model]);
            return $models[0] ?? null;
        }
        
        return $model;
    }

    /**
     * Get access to underlying QueryBuilder
     */
    public function qb(): QueryBuilder
    {
        return $this->qb;
    }

    /**
     * Hydrate array of rows into model instances
     * 
     * @param array $rows
     * @return T[]
     */
    private function hydrateRows(array $rows): array
    {
        return array_map(fn($row) => $this->hydrateRow($row), $rows);
    }

    /**
     * Hydrate single row into model instance
     * 
     * @param array $row
     * @return T
     */
    private function hydrateRow(array $row): BaseModel
    {
        $model = $this->modelClass::fromRow($row);
        
        // Store the original row data for relation loading
        $reflection = new \ReflectionClass($model);
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
        if (empty($models) || empty($this->eagerRelations)) {
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
     * @param string $relation
     */
    private function loadRelation(array $models, string $relation): void
    {
        if (empty($models)) {
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
     * @param string $relation
     */
    private function loadBelongsTo(array $models, string $relation): void
    {
        $modelClass = $this->modelClass;
        [$fkColumn, $relatedTable, $relatedClass] = $modelClass::inferForeignKeyFromTypedProperty($relation);

        // Collect all foreign key values
        $fkValues = [];
        foreach ($models as $model) {
            $reflection = new \ReflectionClass($model);
            if ($reflection->hasProperty('__row')) {
                $rowProperty = $reflection->getProperty('__row');
                $rowProperty->setAccessible(true);
                $row = $rowProperty->getValue($model);
                
                if (isset($row[$fkColumn]) && $row[$fkColumn] !== null) {
                    $fkValues[] = $row[$fkColumn];
                }
            }
        }

        if (empty($fkValues)) {
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
            $reflection = new \ReflectionClass($model);
            if ($reflection->hasProperty('__row')) {
                $rowProperty = $reflection->getProperty('__row');
                $rowProperty->setAccessible(true);
                $row = $rowProperty->getValue($model);
                
                $fkValue = $row[$fkColumn] ?? null;
                if ($fkValue && isset($relatedByID[$fkValue])) {
                    // Set the relation property on the model
                    if ($reflection->hasProperty($relation)) {
                        $relationProperty = $reflection->getProperty($relation);
                        $relationProperty->setAccessible(true);
                        $relationProperty->setValue($model, $relatedByID[$fkValue]);
                    }
                }
            }
        }
    }

    /**
     * Load hasMany relations with a single query
     * 
     * @param T[] $models
     * @param string $relation
     */
    private function loadHasMany(array $models, string $relation): void
    {
        $modelClass = $this->modelClass;
        [$fkColumn, $relatedClass] = $modelClass::inferHasMany($relation);

        // Collect all IDs from parent models
        $parentIds = array_map(fn($model) => $model->id, $models);

        if (empty($parentIds)) {
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
            $reflection = new \ReflectionClass($model);
            if ($reflection->hasProperty($relation)) {
                $relationProperty = $reflection->getProperty($relation);
                $relationProperty->setAccessible(true);
                $relationProperty->setValue($model, $relatedArray);
            }
        }
    }
}
