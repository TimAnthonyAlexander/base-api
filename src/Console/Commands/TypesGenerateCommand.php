<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\OpenApi\OpenApiGenerator;

class TypesGenerateCommand implements Command
{
    private readonly OpenApiGenerator $generator;

    public function __construct()
    {
        $this->generator = new OpenApiGenerator();
    }

    #[Override]
    public function name(): string
    {
        return 'types:generate';
    }

    #[Override]
    public function description(): string
    {
        return 'Generate OpenAPI spec and TypeScript definitions from controllers and routes';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $options = $this->parseArgs($args);

        if (isset($options['help'])) {
            $this->showHelp();
            return 0;
        }

        echo ColorHelper::header("🔧 Generating types from BaseApi controllers and routes...") . "\n";

        try {
            // Step 1: Generate using OpenApiGenerator
            echo ColorHelper::info("📖 Analyzing routes and controllers...") . "\n";
            $spec = $this->generator->generate();
            
            // Step 2: Generate OpenAPI if requested
            if (isset($options['out-openapi'])) {
                echo ColorHelper::info("🌐 Writing OpenAPI spec...") . "\n";
                $this->writeOpenApiSpec($spec, $options);
            }

            // Step 3: Generate TypeScript if requested
            if (isset($options['out-ts'])) {
                echo ColorHelper::info("🔷 Generating TypeScript definitions...") . "\n";
                $this->generateTypeScriptFromSpec($spec, $options);
            }

            echo ColorHelper::success("Type generation completed!") . "\n";
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function parseArgs(array $args): array
    {
        $options = [];

        foreach ($args as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $options['help'] = true;
            } elseif (str_starts_with((string) $arg, '--out-ts=')) {
                $options['out-ts'] = substr((string) $arg, 9);
            } elseif (str_starts_with((string) $arg, '--out-openapi=')) {
                $options['out-openapi'] = substr((string) $arg, 14);
            } elseif (str_starts_with((string) $arg, '--format=')) {
                $options['format'] = substr((string) $arg, 9);
            } elseif (str_starts_with((string) $arg, '--schemas-dir=')) {
                $options['schemas-dir'] = substr((string) $arg, 14);
            }
        }

        // Defaults
        if (!isset($options['format'])) {
            $options['format'] = 'json';
        }

        if (!isset($options['out-ts'])) {
            $options['out-ts'] = 'types.ts';
        }

        if (!isset($options['out-openapi'])) {
            $options['out-openapi'] = 'openapi.json';
        }

        return $options;
    }

    private function showHelp(): void
    {
        echo <<<HELP
Generate OpenAPI spec and TypeScript definitions

Usage:
  ./mason types:generate [options]

Options:
  --out-ts=PATH          Output path for TypeScript definitions (default: types.ts)
  --out-openapi=PATH     Output path for OpenAPI specification (default: openapi.json)
  --format=FORMAT        OpenAPI format: json (default) or yaml
  --schemas-dir=PATH     Output directory for individual JSON schemas
  --help, -h             Show this help message

Examples:
  ./mason types:generate
  ./mason types:generate --out-ts=web/types/baseapi.d.ts
  ./mason types:generate --out-openapi=storage/openapi.json
  ./mason types:generate --out-ts=types.d.ts --out-openapi=api.json --format=yaml

HELP;
    }

    private function writeOpenApiSpec(array $spec, array $options): void
    {
        $output = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->ensureDirectoryExists(dirname((string) $options['out-openapi']));

        if (file_put_contents($options['out-openapi'], $output) === false) {
            throw new Exception('Failed to write OpenAPI spec to ' . $options['out-openapi']);
        }

        echo ColorHelper::success(sprintf('   📄 OpenAPI spec written to %s', $options['out-openapi'])) . "\n";
    }

    private function generateTypeScriptFromSpec(array $spec, array $options): void
    {
        $ts = [];

        // Header and base types
        $ts[] = "// Generated TypeScript definitions for BaseApi";
        $ts[] = "// Do not edit manually - regenerate with: ./mason types:generate";
        $ts[] = "";
        $ts[] = "export type UUID = string;";
        $ts[] = "export type Envelope<T> = { data: T };";
        $ts[] = "";
        $ts[] = "export interface ErrorResponse {";
        $ts[] = "  error: string;";
        $ts[] = "  requestId: string;";
        $ts[] = "  errors?: Record<string, string>;";
        $ts[] = "}";
        $ts[] = "";

        // Generate DTO interfaces from OpenAPI components/schemas
        if (isset($spec['components']['schemas'])) {
            foreach ($spec['components']['schemas'] as $name => $schema) {
                if ($name === 'ErrorResponse') {
                    continue;
                } // Skip error response, already defined
                
                $ts = array_merge($ts, $this->generateTypeScriptInterfaceFromSchema($name, $schema));
                $ts[] = "";
            }
        }

        // Generate request/response types for each path
        if (isset($spec['paths'])) {
            foreach ($spec['paths'] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    $ts = array_merge($ts, $this->generateTypeScriptOperationFromSpec($operation, $method, $path));
                    $ts[] = "";
                }
            }
        }

        $output = implode("\n", $ts);

        $this->ensureDirectoryExists(dirname((string) $options['out-ts']));

        if (file_put_contents($options['out-ts'], $output) === false) {
            throw new Exception('Failed to write TypeScript definitions to ' . $options['out-ts']);
        }

        echo ColorHelper::success(sprintf('   📘 TypeScript definitions written to %s', $options['out-ts'])) . "\n";
    }

    private function generateTypeScriptInterfaceFromSchema(string $name, array $schema): array
    {
        $lines = [];
        $lines[] = sprintf('export interface %s {', $name);

        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propSchema) {
                $tsType = $this->openApiTypeToTypeScript($propSchema);
                $required = isset($schema['required']) && in_array($propName, $schema['required']);
                $optional = $required ? '' : '?';
                $lines[] = sprintf('  %s%s: %s;', $propName, $optional, $tsType);
            }
        }

        $lines[] = "}";
        return $lines;
    }

    private function generateTypeScriptOperationFromSpec(array $operation, string $method, string $path): array
    {
        $lines = [];
        $operationName = $this->getOperationNameFromSpec($operation, $method, $path);

        // Generate path parameters interface if needed
        $pathParams = $this->getPathParametersFromSpec($operation);
        if ($pathParams !== []) {
            $lines[] = sprintf('export interface %sRequestPath {', $operationName);
            foreach ($pathParams as $param) {
                $tsType = $this->openApiTypeToTypeScript($param['schema']);
                $lines[] = sprintf('  %s: %s;', $param['name'], $tsType);
            }

            $lines[] = "}";
            $lines[] = "";
        }

        // Generate query/body parameters interface if needed
        $queryParams = $this->getQueryParametersFromSpec($operation);
        $requestBody = $this->getRequestBodyFromSpec($operation);
        
        if ($queryParams !== [] || $requestBody !== []) {
            $interfaceType = $method === 'get' ? 'Query' : 'Body';
            $lines[] = sprintf('export interface %sRequest%s {', $operationName, $interfaceType);
            
            // Add query parameters
            foreach ($queryParams as $param) {
                $tsType = $this->openApiTypeToTypeScript($param['schema']);
                $optional = $param['required'] ? '' : '?';
                $lines[] = sprintf('  %s%s: %s;', $param['name'], $optional, $tsType);
            }
            
            // Add request body properties
            if ($requestBody !== [] && isset($requestBody['properties'])) {
                foreach ($requestBody['properties'] as $propName => $propSchema) {
                    $tsType = $this->openApiTypeToTypeScript($propSchema);
                    $required = isset($requestBody['required']) && in_array($propName, $requestBody['required']);
                    $optional = $required ? '' : '?';
                    $lines[] = sprintf('  %s%s: %s;', $propName, $optional, $tsType);
                }
            }
            
            $lines[] = "}";
            $lines[] = "";
        }

        // Generate response type
        $responseType = $this->getResponseTypeFromSpec($operation);
        $lines[] = sprintf('export type %sResponse = Envelope<%s>;', $operationName, $responseType);

        return $lines;
    }

    private function openApiTypeToTypeScript(array $schema): string
    {
        if (isset($schema['$ref'])) {
            // Extract type name from reference
            $ref = $schema['$ref'];
            $parts = explode('/', (string) $ref);
            return end($parts);
        }

        if (isset($schema['type'])) {
            switch ($schema['type']) {
                case 'integer':
                case 'number':
                    return 'number';
                case 'string':
                    return 'string';
                case 'boolean':
                    return 'boolean';
                case 'array':
                    if (isset($schema['items'])) {
                        $itemType = $this->openApiTypeToTypeScript($schema['items']);
                        return $itemType . '[]';
                    }

                    return 'any[]';
                case 'object':
                    if (isset($schema['properties'])) {
                        $props = [];
                        foreach ($schema['properties'] as $propName => $propSchema) {
                            $propType = $this->openApiTypeToTypeScript($propSchema);
                            $props[] = sprintf('%s: %s', $propName, $propType);
                        }

                        return '{ ' . implode('; ', $props) . ' }';
                    }

                    return 'Record<string, any>';
                default:
                    return 'any';
            }
        }

        return 'any';
    }

    private function getOperationNameFromSpec(array $operation, string $method, string $path): string
    {
        if (isset($operation['operationId'])) {
            // Convert operationId to PascalCase for interface names
            return ucfirst((string) $operation['operationId']);
        }

        // Fallback: generate from method and path
        $pathParts = array_filter(explode('/', $path));
        $pathName = implode('', array_map('ucfirst', $pathParts));
        return ucfirst($method) . $pathName;
    }

    private function getPathParametersFromSpec(array $operation): array
    {
        if (!isset($operation['parameters'])) {
            return [];
        }

        return array_filter($operation['parameters'], fn($param): bool => $param['in'] === 'path');
    }

    private function getQueryParametersFromSpec(array $operation): array
    {
        if (!isset($operation['parameters'])) {
            return [];
        }

        return array_filter($operation['parameters'], fn($param): bool => $param['in'] === 'query');
    }

    private function getRequestBodyFromSpec(array $operation): array
    {
        if (!isset($operation['requestBody']['content']['application/json']['schema'])) {
            return [];
        }

        return $operation['requestBody']['content']['application/json']['schema'];
    }

    private function getResponseTypeFromSpec(array $operation): string
    {
        // Look for successful response (200, 201, etc.)
        foreach ($operation['responses'] as $status => $response) {
            // 2xx status codes
            if ($status[0] === '2' && isset($response['content']['application/json']['schema']['properties']['data'])) { return $this->openApiTypeToTypeScript($response['content']['application/json']['schema']['properties']['data']);
            }
        }

        return 'any';
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new Exception('Failed to create directory: ' . $directory);
        }
    }
}