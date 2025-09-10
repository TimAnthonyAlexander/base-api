<?php

namespace BaseApi\Models;

/**
 * User Model
 */
class User extends BaseModel
{
    public string $name = '';
    public string $email = '';
    public bool $active = true;
    
    // Optional: Define indexes for migration generation
    public static array $indexes = [
        'email' => 'unique',
        'created_at' => 'index'
    ];
}

