<?php

namespace BaseApi\Models\Traits;

use BaseApi\App;
use BaseApi\Database\ModelQuery;

trait SoftDeletes
{
    /**
     * Boot the soft deletes trait for a model.
     */
    public static function bootSoftDeletes(): void
    {
        // Add a global scope to exclude soft deleted records by default
        static::addGlobalScope('softDeletes', function (ModelQuery $query): void {
            $query->whereNull('deleted_at');
        });
    }

    /**
     * Query including soft deleted records
     */
    public static function withTrashed(): ModelQuery
    {
        // Create a fresh query without global scopes
        $qb = App::db()->qb()->table(static::table());
        return new ModelQuery($qb, static::class);
    }

    /**
     * Query only soft deleted records
     */
    public static function onlyTrashed(): ModelQuery
    {
        return static::withTrashed()->whereNotNull('deleted_at');
    }

    /**
     * Soft delete the model (set deleted_at timestamp)
     */
    public function delete(): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $this->deleted_at = date('Y-m-d H:i:s');

        return $this->save();
    }

    /**
     * Restore a soft deleted model
     */
    public function restore(): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $this->deleted_at = null;

        return $this->save();
    }

    /**
     * Permanently delete the model from database
     */
    public function forceDelete(): bool
    {
        if (empty($this->id)) {
            return false;
        }

        return parent::delete();
    }

    /**
     * Check if the model is soft deleted
     */
    public function trashed(): bool
    {
        return $this->deleted_at !== null;
    }
}
