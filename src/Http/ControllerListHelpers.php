<?php

namespace BaseApi\Http;

use BaseApi\Database\QueryBuilder;
use BaseApi\Database\ModelQuery;

class ControllerListHelpers
{
    /**
     * Apply pagination parameters from request
     *
     * @return array [$q, $page, $perPage, $withTotal]
     */
    public static function applyPagination(QueryBuilder|ModelQuery $q, Request $req, int $maxPerPage): array
    {
        $page = max(1, (int) ($req->query['page'] ?? 1));
        $perPage = max(1, min($maxPerPage, (int) ($req->query['perPage'] ?? 20)));
        $withTotal = isset($req->query['withTotal']) && $req->query['withTotal'] !== 'false';

        return [$q, $page, $perPage, $withTotal];
    }

    /**
     * Apply sort parameter from request
     * Supports sort=name,-createdAt format
     */
    public static function applySort(QueryBuilder|ModelQuery $q, ?string $sort): void
    {
        if (!$sort) {
            return;
        }

        if ($q instanceof QueryBuilder) {
            $q->applySortString($sort);
        } elseif ($q instanceof ModelQuery) {
            // Parse sort string and apply to ModelQuery
            $sorts = explode(',', $sort);
            foreach ($sorts as $field) {
                $field = trim($field);
                if ($field === '') {
                    continue;
                }

                if ($field === '0') {
                    continue;
                }

                $direction = 'asc';
                if (str_starts_with($field, '-')) {
                    $direction = 'desc';
                    $field = substr($field, 1);
                }

                // Convert camelCase to snake_case
                $column = self::camelToSnake($field);
                $q->orderBy($column, $direction);
            }
        }
    }

    /**
     * Apply filter parameters from request
     * Supports filter[field]=value format
     */
    public static function applyFilters(QueryBuilder|ModelQuery $q, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if ($value === null) {
                continue;
            }

            if ($value === '') {
                continue;
            }

            // Convert camelCase to snake_case
            $column = self::camelToSnake($field);

            if ($q instanceof QueryBuilder) {
                $q->where($column, '=', $value);
            } elseif ($q instanceof ModelQuery) {
                $q->where($column, '=', $value);
            }
        }
    }

    /**
     * Apply all common list parameters (pagination, sort, filter) from request
     *
     * @return array [$q, $page, $perPage, $withTotal]
     */
    public static function applyListParams(QueryBuilder|ModelQuery $q, Request $req, int $maxPerPage): array
    {
        [$q, $page, $perPage, $withTotal] = self::applyPagination($q, $req, $maxPerPage);
        
        self::applySort($q, $req->query['sort'] ?? null);
        self::applyFilters($q, $req->query['filter'] ?? []);

        return [$q, $page, $perPage, $withTotal];
    }

    /**
     * Convert camelCase to snake_case for database column names
     */
    private static function camelToSnake(string $input): string
    {
        return strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }
}
