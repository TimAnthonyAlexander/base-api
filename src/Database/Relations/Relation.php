<?php

namespace BaseApi\Database\Relations;

use BaseApi\Models\BaseModel;

/**
 * Base Relation class that defines the interface for all relation types
 */
abstract class Relation
{
    protected BaseModel $parent;
    protected string $relatedClass;
    protected ?string $foreignKey = null;
    protected ?string $localKey = null;

    public function __construct(BaseModel $parent, string $relatedClass, ?string $foreignKey = null, ?string $localKey = null)
    {
        $this->parent = $parent;
        $this->relatedClass = $relatedClass;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey ?? 'id';
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
