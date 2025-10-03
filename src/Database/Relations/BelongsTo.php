<?php

namespace BaseApi\Database\Relations;

use Override;
use ReflectionClass;
use BaseApi\Models\BaseModel;

/**
 * Represents a belongsTo relationship (many-to-one)
 */
class BelongsTo extends Relation
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

        // Prefer relation method name (e.g. 'watchItem' becomes 'watch_item_id')
        if ($this->relationName !== null) {
            return $this->toSnakeCase($this->relationName) . '_id';
        }

        // Fall back to related class name in snake_case
        $relatedClass = $this->relatedClass;
        $className = (new ReflectionClass($relatedClass))->getShortName();
        return $this->toSnakeCase($className) . '_id';
    }

    /**
     * Execute the relation and return the related model or null
     */
    #[Override]
    public function get(): ?BaseModel
    {
        return $this->first();
    }

    /**
     * Execute the relation and return the first (and only) related model
     */
    #[Override]
    public function first(): ?BaseModel
    {
        $foreignKey = $this->getForeignKey();
        // Access property directly - __row is protected and can't be accessed from outside
        $foreignKeyValue = $this->parent->$foreignKey ?? null;

        if (!$foreignKeyValue) {
            return null;
        }

        $relatedClass = $this->relatedClass;
        return $relatedClass::find($foreignKeyValue);
    }

    /**
     * Check if the relation exists
     */
    public function exists(): bool
    {
        $foreignKey = $this->getForeignKey();
        // Access property directly - __row is protected and can't be accessed from outside
        $foreignKeyValue = $this->parent->$foreignKey ?? null;

        return $foreignKeyValue !== null;
    }
}
