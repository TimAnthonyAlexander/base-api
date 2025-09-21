<?php

namespace BaseApi\Auth;

/**
 * Contract for resolving users by ID.
 * Returns simple associative arrays (KISS approach).
 */
interface UserProvider
{
    /**
     * Resolve a user by ID.
     * 
     * @param string $id User ID
     * @return array<string, mixed>|null Simple associative array like ['id' => '...', 'email' => '...'] or null
     */
    public function byId(string $id): ?array;
}
