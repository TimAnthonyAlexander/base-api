<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\IR;

readonly class ModelIR
{
    public function __construct(
        public string $name,
        public SchemaIR $schema,
        public bool $exposed = true
    ) {}
}


