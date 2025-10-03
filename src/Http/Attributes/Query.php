<?php

declare(strict_types=1);

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Marks a controller property as a query parameter
 * Useful for DELETE/POST/PUT/PATCH operations that accept query parameters
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Query
{
    public function __construct(
        public ?string $name = null,
        public bool $required = false
    ) {}
}


