<?php

declare(strict_types=1);

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Tells the OpenAPI/TypeScript generator whether success responses are wrapped
 * in an envelope { data: T }. Without it, the generator follows the
 * `response.wrap_data` config, like JsonResponse does.
 *
 * This only affects generated types. It does not change the runtime response:
 * pair it with the matching `wrap:` argument, e.g. JsonResponse::ok($x, wrap: false).
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Enveloped
{
    public function __construct(
        public bool $enabled = true
    ) {}
}


