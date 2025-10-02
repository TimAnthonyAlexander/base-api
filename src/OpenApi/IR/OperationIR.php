<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\IR;

readonly class OperationIR
{
    /**
     * @param string $method GET | POST | PUT | PATCH | DELETE
     * @param string $path /users/{id}
     * @param string[] $tags
     * @param ParamIR[] $pathParams
     * @param ParamIR[] $queryParams
     * @param array|null $body { schema: SchemaIR, required: bool }
     * @param array[] $responses [{ status: int, schema: SchemaIR|null }]
     * @param array|null $envelope { type: "Envelope", dataRef: string }
     */
    public function __construct(
        public string $operationId,
        public string $method,
        public string $path,
        public array $tags = [],
        public array $pathParams = [],
        public array $queryParams = [],
        public ?array $body = null,
        public array $responses = [],
        public ?array $envelope = null,
        public ?string $errorRef = null,
    ) {}

    public function getSuccessResponse(): ?array
    {
        foreach ($this->responses as $response) {
            if ($response['status'] >= 200 && $response['status'] < 300) {
                return $response;
            }
        }

        return null;
    }
}

