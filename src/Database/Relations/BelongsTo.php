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

        // Default to relationName_id (e.g. 'user' becomes 'user_id')
        $relatedClass = $this->relatedClass;
        $className = (new ReflectionClass($relatedClass))->getShortName();
        return strtolower($className) . '_id';
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
