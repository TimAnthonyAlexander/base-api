<?php

namespace BaseApi\Database\Relations;

use BaseApi\Models\BaseModel;

/**
 * Base Relation class that defines the interface for all relation types
 */
abstract class Relation
{
    protected ?string $localKey = null;

    public function __construct(protected BaseModel $parent, protected string $relatedClass, protected ?string $foreignKey = null, ?string $localKey = null, protected ?string $relationName = null)
    {
        $this->localKey = $localKey ?? 'id';
    }

    /**
     * Convert a string from PascalCase or camelCase to snake_case
     */
    protected function toSnakeCase(string $string): string
    {
        // Insert underscore before uppercase letters and convert to lowercase
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);
        return strtolower((string) $snake);
    }

    /**
     * Get the foreign key name
     */
    abstract protected function getForeignKey(): string;

    /**
     * Get the local key name  
     */
    protected function getLocalKey(): string
    {
        return $this->localKey ?? 'id';
    }

    /**
     * Execute the relation query and return results
     * @return BaseModel|BaseModel[]|null
     */
    abstract public function get(): mixed;

    /**
     * Execute the relation query and return first result
     */
    abstract public function first(): ?BaseModel;

    /**
     * Get the related model class name
     */
    public function getRelatedClass(): string
    {
        return $this->relatedClass;
    }

    /**
     * Get the parent model
     */
    public function getParent(): BaseModel
    {
        return $this->parent;
    }
}
