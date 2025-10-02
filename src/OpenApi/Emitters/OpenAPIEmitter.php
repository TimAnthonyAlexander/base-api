<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\Emitters;

use BaseApi\OpenApi\IR\ApiIR;
use BaseApi\OpenApi\IR\OperationIR;
use BaseApi\OpenApi\IR\ParamIR;
use BaseApi\OpenApi\IR\SchemaIR;

class OpenAPIEmitter
{
    public function emit(ApiIR $ir): array
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $ir->title,
                'version' => $ir->version,
                'description' => $ir->description ?? 'Generated API documentation'
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'responses' => []
            ]
        ];

        if ($ir->baseUrl) {
            $spec['servers'] = [['url' => $ir->baseUrl]];
        }

        // Generate paths from operations
        foreach ($ir->operations as $operation) {
            $path = $operation->path;
            $method = strtolower($operation->method);

            if (!isset($spec['paths'][$path])) {
                $spec['paths'][$path] = [];
            }

            $spec['paths'][$path][$method] = $this->emitOperation($operation);
        }

        // Generate component schemas from models
        foreach ($ir->models as $model) {
            $spec['components']['schemas'][$model->name] = $this->emitSchema($model->schema);
        }

        // Add error response schema
        $spec['components']['schemas']['ErrorResponse'] = [
            'type' => 'object',
            'properties' => [
                'error' => ['type' => 'string'],
                'requestId' => ['type' => 'string'],
                'errors' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'string']
                ]
            ],
            'required' => ['error', 'requestId']
        ];

        // Add standard error responses
        $spec['components']['responses']['BadRequest'] = [
            'description' => 'Bad Request',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                ]
            ]
        ];

        $spec['components']['responses']['ServerError'] = [
            'description' => 'Internal Server Error',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                ]
            ]
        ];

        return $spec;
    }

    private function emitOperation(OperationIR $operation): array
    {
        $op = [
            'summary' => $this->generateSummary($operation),
            'operationId' => $operation->operationId,
            'tags' => $operation->tags,
            'parameters' => [],
            'responses' => []
        ];

        // Add path parameters
        foreach ($operation->pathParams as $param) {
            $op['parameters'][] = $this->emitParameter($param, 'path');
        }

        // Add query parameters
        foreach ($operation->queryParams as $param) {
            $op['parameters'][] = $this->emitParameter($param, 'query');
        }

        // Add request body
        if ($operation->body) {
            $op['requestBody'] = [
                'required' => $operation->body['required'],
                'content' => [
                    'application/json' => [
                        'schema' => $this->emitSchema($operation->body['schema'])
                    ]
                ]
            ];
        }

        // Add responses
        foreach ($operation->responses as $response) {
            $status = (string)$response['status'];
            
            if ($response['schema']) {
                // Wrap in envelope if specified
                $schema = $response['schema'];
                if ($operation->envelope && $response['status'] >= 200 && $response['status'] < 300) {
                    $schema = SchemaIR::object([
                        'data' => ['schema' => $schema, 'required' => true]
                    ]);
                }

                $op['responses'][$status] = [
                    'description' => $this->getResponseDescription($response['status']),
                    'content' => [
                        'application/json' => [
                            'schema' => $this->emitSchema($schema)
                        ]
                    ]
                ];
            } else {
                $op['responses'][$status] = [
                    'description' => $this->getResponseDescription($response['status'])
                ];
            }
        }

        // Add standard error responses
        $op['responses']['400'] = ['$ref' => '#/components/responses/BadRequest'];
        $op['responses']['500'] = ['$ref' => '#/components/responses/ServerError'];

        return $op;
    }

    private function emitParameter(ParamIR $param, string $in): array
    {
        return [
            'name' => $param->name,
            'in' => $in,
            'required' => $param->required,
            'schema' => $this->emitSchema($param->schema)
        ];
    }

    private function emitSchema(SchemaIR $schema): array
    {
        return match (true) {
            $schema->isRef() => ['$ref' => '#/components/schemas/' . $schema->data['name']],
            
            $schema->isPrimitive() => $this->emitPrimitiveSchema($schema),
            
            $schema->isArray() => [
                'type' => 'array',
                'items' => $this->emitSchema($schema->data['items'])
            ],
            
            $schema->isObject() => $this->emitObjectSchema($schema),
            
            $schema->isUnion() => [
                $schema->data['kind'] => array_map(
                    fn($member): array => $this->emitSchema($member),
                    $schema->data['members']
                )
            ],
            
            default => ['type' => 'object']
        };
    }

    private function emitPrimitiveSchema(SchemaIR $schema): array
    {
        $result = ['type' => $schema->data['type']];

        if ($schema->data['nullable']) {
            $result['nullable'] = true;
        }

        if ($schema->data['enum']) {
            $result['enum'] = $schema->data['enum'];
        }

        if ($schema->data['format']) {
            $result['format'] = $schema->data['format'];
        }

        return $result;
    }

    private function emitObjectSchema(SchemaIR $schema): array
    {
        $result = ['type' => 'object', 'properties' => []];
        $required = [];

        foreach ($schema->data['properties'] as $name => $prop) {
            $result['properties'][$name] = $this->emitSchema($prop['schema']);
            if ($prop['required']) {
                $required[] = $name;
            }
        }

        if ($required !== []) {
            $result['required'] = $required;
        }

        if ($schema->data['additional']) {
            $result['additionalProperties'] = true;
        }

        return $result;
    }

    private function generateSummary(OperationIR $operation): string
    {
        return $operation->method . ' ' . $operation->path;
    }

    private function getResponseDescription(int $status): string
    {
        return match ($status) {
            200 => 'Success',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            default => 'Response'
        };
    }
}

