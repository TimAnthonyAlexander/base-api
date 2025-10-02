<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\Attributes;

use Attribute;

/**
 * Marks a class as an API model that should be exported
 * even if not currently referenced by any endpoint
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ApiModel
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null
    ) {}
}

