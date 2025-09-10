<?php

namespace BaseApi\Auth;

use BaseApi\Database\DB;

/**
 * Default UserProvider implementation using database or stub fallback.
 */
class SimpleUserProvider implements UserProvider
{
    public function __construct(
        private ?DB $db = null
    ) {}

    /**
     * Resolve user by ID from database or return stub.
     */
    public function byId(string $id): ?array
    {
        if ($this->db === null) {
            // Stub for early adopters without DB setup
            return ['id' => $id];
        }

        try {
            $results = $this->db->raw(
                'SELECT id, email, name, active FROM users WHERE id = ? LIMIT 1',
                [$id]
            );

            if (empty($results)) {
                return null;
            }

            return $results[0];
        } catch (\Exception $e) {
            // If table doesn't exist, return stub
            return ['id' => $id];
        }
    }
}
