<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\IR;

readonly class ParamIR
{
    public function __construct(
        public string $name,
        public SchemaIR $schema,
        public bool $required = false,
        public string $style = 'simple'
    ) {}
}


