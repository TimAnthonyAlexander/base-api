<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\Emitters;

use BaseApi\OpenApi\IR\ApiIR;
use BaseApi\OpenApi\IR\OperationIR;
use BaseApi\OpenApi\IR\SchemaIR;

class TypeScriptTypesEmitter
{
    public function emit(ApiIR $ir): string
    {
        $lines = [];

        // Header
        $lines[] = '// Generated TypeScript definitions for ' . $ir->title;
        $lines[] = "// Do not edit manually - regenerate with: ./mason types:generate";
        $lines[] = "";

        // Base types
        $lines[] = "export type UUID = string;";
        $lines[] = "export type Envelope<T> = { data: T };";
        $lines[] = "";

        // Error response
        $lines[] = "export interface ErrorResponse {";
        $lines[] = "  error: string;";
        $lines[] = "  requestId: string;";
        $lines[] = "  errors?: Record<string, string>;";
        $lines[] = "}";
        $lines[] = "";

        // Generate model interfaces
        foreach ($ir->models as $model) {
            $lines = array_merge($lines, $this->emitModel($model->name, $model->schema));
            $lines[] = "";
        }

        // Generate operation types
        foreach ($ir->operations as $operation) {
            $lines = array_merge($lines, $this->emitOperation($operation));
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    private function emitModel(string $name, SchemaIR $schema): array
    {
        $lines = [];
        $lines[] = sprintf('export interface %s {', $name);

        if ($schema->isObject()) {
            foreach ($schema->data['properties'] as $propName => $prop) {
                $tsType = $this->schemaToTypeScript($prop['schema']);
                $optional = $prop['required'] ? '' : '?';
                $lines[] = sprintf('  %s%s: %s;', $propName, $optional, $tsType);
            }
        }

        $lines[] = "}";
        return $lines;
    }

    private function emitOperation(OperationIR $operation): array
    {
        $lines = [];
        $opName = $this->toPascalCase($operation->operationId);

        // Path parameters interface
        if ($operation->pathParams !== []) {
            $lines[] = sprintf('export interface %sPathParams {', $opName);
            foreach ($operation->pathParams as $param) {
                $tsType = $this->schemaToTypeScript($param->schema);
                $lines[] = sprintf('  %s: %s;', $param->name, $tsType);
            }

            // Add index signature for compatibility with buildPath function
            $lines[] = "  [key: string]: string | number | null;";
            $lines[] = "}";
            $lines[] = "";
        }

        // Query parameters interface
        if ($operation->queryParams !== []) {
            $lines[] = sprintf('export interface %sQueryParams {', $opName);
            foreach ($operation->queryParams as $param) {
                $tsType = $this->schemaToTypeScript($param->schema);
                $optional = $param->required ? '' : '?';
                $lines[] = sprintf('  %s%s: %s;', $param->name, $optional, $tsType);
            }

            $lines[] = "}";
            $lines[] = "";
        }

        // Request body interface
        if ($operation->body) {
            $interfaceType = $operation->method === 'GET' ? 'Query' : 'Body';
            $lines[] = sprintf('export interface %sRequest%s {', $opName, $interfaceType);
            
            if ($operation->body['schema']->isObject()) {
                foreach ($operation->body['schema']->data['properties'] as $propName => $prop) {
                    $tsType = $this->schemaToTypeScript($prop['schema']);
                    $optional = $prop['required'] ? '' : '?';
                    $lines[] = sprintf('  %s%s: %s;', $propName, $optional, $tsType);
                }
            }
            
            $lines[] = "}";
            $lines[] = "";
        }

        // Response type
        $successResponse = $operation->getSuccessResponse();
        if ($successResponse && $successResponse['schema']) {
            $responseType = $this->schemaToTypeScript($successResponse['schema']);
        } else {
            $responseType = 'unknown';
        }

        // Wrap in envelope if specified
        if ($operation->envelope && $successResponse) {
            $lines[] = sprintf('export type %sResponse = Envelope<%s>;', $opName, $responseType);
        } else {
            $lines[] = sprintf('export type %sResponse = %s;', $opName, $responseType);
        }

        return $lines;
    }

    private function schemaToTypeScript(SchemaIR $schema): string
    {
        return match (true) {
            $schema->isRef() => $schema->data['name'],
            
            $schema->isPrimitive() => $this->primitiveToTypeScript($schema),
            
            $schema->isArray() => $this->schemaToTypeScript($schema->data['items']) . '[]',
            
            $schema->isObject() => $this->objectToTypeScript($schema),
            
            $schema->isUnion() => $this->unionToTypeScript($schema),
            
            default => 'unknown'
        };
    }

    private function primitiveToTypeScript(SchemaIR $schema): string
    {
        $tsType = match ($schema->data['type']) {
            'integer', 'number' => 'number',
            'string' => 'string',
            'boolean' => 'boolean',
            default => 'unknown'
        };

        if ($schema->data['enum']) {
            $enumValues = array_map(
                fn($v): mixed => is_string($v) ? sprintf("'%s'", $v) : $v,
                $schema->data['enum']
            );
            $tsType = implode(' | ', $enumValues);
        }

        if ($schema->data['nullable']) {
            $tsType .= ' | null';
        }

        return $tsType;
    }

    private function objectToTypeScript(SchemaIR $schema): string
    {
        if (empty($schema->data['properties'])) {
            return 'Record<string, unknown>';
        }

        $props = [];
        foreach ($schema->data['properties'] as $propName => $prop) {
            $propType = $this->schemaToTypeScript($prop['schema']);
            $optional = $prop['required'] ? '' : '?';
            $props[] = sprintf('%s%s: %s', $propName, $optional, $propType);
        }

        return '{ ' . implode('; ', $props) . ' }';
    }

    private function unionToTypeScript(SchemaIR $schema): string
    {
        $members = array_map(
            fn($member): string => $this->schemaToTypeScript($member),
            $schema->data['members']
        );

        return '(' . implode(' | ', $members) . ')';
    }

    private function toPascalCase(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }
}


