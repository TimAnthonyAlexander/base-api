<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\IR;

readonly class RouteIR
{
    public function __construct(
        public string $key,
        public string $template
    ) {}
}


