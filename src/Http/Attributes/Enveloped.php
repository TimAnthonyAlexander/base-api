<?php

declare(strict_types=1);

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Controls whether responses are wrapped in an envelope { data: T }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Enveloped
{
    public function __construct(
        public bool $enabled = true
    ) {}
}

