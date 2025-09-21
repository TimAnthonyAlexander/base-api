<?php

namespace BaseApi\Database\Relations;

use Override;
use BaseApi\Models\BaseModel;
use BaseApi\Database\QueryBuilder;

/**
 * Represents a hasMany relationship (one-to-many)
 */
class HasMany extends Relation
{
    /**
     * Get the foreign key name for this relation
     */
    #[Override]
    protected function getForeignKey(): string
    {
        if ($this->foreignKey !== null) {
            return $this->foreignKey;
        }

        // Default to parentTable_id (e.g. 'users' becomes 'user_id')
        $parentTable = $this->parent::table();
        $singularTable = BaseModel::singularize($parentTable);
        return $singularTable . '_id';
    }


    /**
     * Execute the relation and return array of related models
     * @return BaseModel[]
     */
    #[Override]
    public function get(): array
    {
        $foreignKey = $this->getForeignKey();
        $localKeyValue = $this->parent->{$this->getLocalKey()};

        if (!$localKeyValue) {
            return [];
        }

        $relatedClass = $this->relatedClass;
        return $relatedClass::where($foreignKey, '=', $localKeyValue)->get();
    }

    /**
     * Execute the relation and return first related model
     */
    #[Override]
    public function first(): ?BaseModel
    {
        $foreignKey = $this->getForeignKey();
        $localKeyValue = $this->parent->{$this->getLocalKey()};

        if (!$localKeyValue) {
            return null;
        }

        $relatedClass = $this->relatedClass;
        $row = $relatedClass::where($foreignKey, '=', $localKeyValue)->first();
        return $row ? $relatedClass::fromRow($row) : null;
    }

    /**
     * Check if any related models exist
     */
    public function exists(): bool
    {
        $foreignKey = $this->getForeignKey();
        $localKeyValue = $this->parent->{$this->getLocalKey()};

        if (!$localKeyValue) {
            return false;
        }

        $relatedClass = $this->relatedClass;
        return $relatedClass::exists($foreignKey, '=', $localKeyValue);
    }

    /**
     * Count related models
     */
    public function count(): int
    {
        $foreignKey = $this->getForeignKey();
        $localKeyValue = $this->parent->{$this->getLocalKey()};

        if (!$localKeyValue) {
            return 0;
        }

        $relatedClass = $this->relatedClass;
        return $relatedClass::countWhere($foreignKey, '=', $localKeyValue);
    }

    /**
     * Get a QueryBuilder for further chaining
     */
    public function query(): QueryBuilder
    {
        $foreignKey = $this->getForeignKey();
        $localKeyValue = $this->parent->{$this->getLocalKey()};

        $relatedClass = $this->relatedClass;
        return $relatedClass::where($foreignKey, '=', $localKeyValue);
    }
}
