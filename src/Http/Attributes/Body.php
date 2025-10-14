<?php

declare(strict_types=1);

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Marks a controller property as part of the request body
 * Useful for explicitly marking body parameters
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Body
{
    public function __construct(
        public ?string $name = null,
        public bool $required = false
    ) {}
}


