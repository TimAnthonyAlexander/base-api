<?php

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Groups endpoints in OpenAPI documentation.
 * 
 * Usage:
 * #[Tag('Users', 'Authentication')]
 * class UserController extends Controller { ... }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Tag
{
    public array $tags;

    public function __construct(string ...$tags)
    {
        $this->tags = $tags;
    }
}
