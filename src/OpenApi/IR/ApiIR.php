<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\IR;

/**
 * Complete intermediate representation of the API
 */
readonly class ApiIR
{
    /**
     * @param OperationIR[] $operations
     * @param ModelIR[] $models
     * @param RouteIR[] $routes
     */
    public function __construct(
        public string $title,
        public string $version,
        public ?string $description = null,
        public ?string $baseUrl = null,
        public array $operations = [],
        public array $models = [],
        public array $routes = [],
    ) {}
}

