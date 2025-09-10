<?php

namespace BaseApi\Dto;

/**
 * User Data Transfer Object for API responses
 */
class UserDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $avatar = null,
        public bool $isActive = true
    ) {
    }
}
