<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\Builders;

use BaseApi\App;
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\Http\Attributes\Tag;
use BaseApi\Http\Attributes\Query;
use BaseApi\Http\Attributes\Body;
use BaseApi\Http\Attributes\Rules;
use BaseApi\Http\Attributes\Enveloped;
use BaseApi\OpenApi\IR\ApiIR;
use BaseApi\OpenApi\IR\ModelIR;
use BaseApi\OpenApi\IR\OperationIR;
use BaseApi\OpenApi\IR\ParamIR;
use BaseApi\OpenApi\IR\RouteIR;
use BaseApi\OpenApi\IR\SchemaIR;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class IRBuilder
{
    private array $routes = [];

    private array $models = [];

    private array $processedModels = [];

    public function build(): ApiIR
    {
        // Step 1: Load routes
        $this->loadRoutes();

        // Step 2: Build operations from routes
        $operations = [];
        $routeIRs = [];

        foreach ($this->routes as $route) {
            $operation = $this->buildOperation($route);
            if ($operation instanceof OperationIR) {
                $operations[] = $operation;
                $routeIRs[] = new RouteIR($operation->operationId, $route['path']);
            }
        }

        // Step 3: Build models
        $modelIRs = [];
        foreach ($this->models as $modelName => $modelData) {
            $modelIRs[] = new ModelIR($modelName, $modelData['schema'], true);
        }

        return new ApiIR(
            title: $_ENV['APP_NAME'] ?? 'BaseApi',
            version: '1.0.0',
            description: 'Generated API documentation',
            baseUrl: $_ENV['APP_URL'] ?? null,
            operations: $operations,
            models: $modelIRs,
            routes: $routeIRs,
        );
    }

    private function loadRoutes(): void
    {
        $routesFile = App::basePath('routes/api.php');

        if (!file_exists($routesFile)) {
            throw new Exception('Routes file not found: ' . $routesFile);
        }

        $this->parseRoutesFile($routesFile);
    }

    private function parseRoutesFile(string $routesFile): void
    {
        $content = file_get_contents($routesFile);
        $routes = [];

        // Match all HTTP verbs including OPTIONS and HEAD
        $pattern = '/\$router->(get|post|delete|put|patch|options|head)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[(.*?)\]\s*,?\s*\);/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $method = strtoupper($match[1]);
                $path = $match[2];
                $pipelineContent = $match[3];

                // Extract pipeline classes - support both ShortName::class and Full\Namespace\Class::class
                $pipeline = [];
                $classPattern = '/([A-Za-z_][A-Za-z0-9_\\\\]*)\s*::\s*class/';
                if (preg_match_all($classPattern, $pipelineContent, $classMatches)) {
                    foreach ($classMatches[1] as $className) {
                        $pipeline[] = $this->resolveClassName($className, $content);
                    }
                }

                $routes[] = [
                    'method' => $method,
                    'path' => $path,
                    'pipeline' => $pipeline
                ];
            }
        }

        $this->routes = $routes;
    }

    private function resolveClassName(string $shortName, string $content): string
    {
        $usePattern = '/use\s+([^;]+);/';
        if (preg_match_all($usePattern, $content, $matches)) {
            foreach ($matches[1] as $useStatement) {
                $parts = explode('\\', trim($useStatement));
                $className = end($parts);
                if ($className === $shortName) {
                    return trim($useStatement);
                }
            }
        }

        return $shortName;
    }

    private function buildOperation(array $route): ?OperationIR
    {
        $pipeline = $route['pipeline'];
        $controllerClass = end($pipeline);

        if (!class_exists($controllerClass)) {
            return null;
        }

        $reflection = new ReflectionClass($controllerClass);
        $methodName = $this->getControllerMethodName($route['method']);

        if (!$reflection->hasMethod($methodName)) {
            $methodName = 'action';
        }

        if (!$reflection->hasMethod($methodName)) {
            return null;
        }

        $methodReflection = $reflection->getMethod($methodName);

        // Extract path parameters
        preg_match_all('/\{(\w+)\}/', (string) $route['path'], $pathMatches);
        $pathParamNames = $pathMatches[1];

        // Get all parameters from controller properties
        $allParams = $this->getControllerParameters($reflection);

        // Separate into path, query, and body using attributes and conventions
        $pathParams = [];
        $queryParams = [];
        $bodyParams = [];

        foreach ($allParams as $param) {
            $property = $param['property'] ?? null;

            if (in_array($param['name'], $pathParamNames)) {
                // Path parameters
                $pathParams[] = new ParamIR(
                    $param['name'],
                    $this->phpTypeToSchemaIR($param['type'], $param['nullable']),
                    true
                );
            } elseif ($param['isQuery'] ?? false) {
                // Explicitly marked as Query
                $queryParams[] = new ParamIR(
                    $param['name'],
                    $this->phpTypeToSchemaIR($param['type'], $param['nullable']),
                    $param['required']
                );
            } elseif ($param['isBody'] ?? false) {
                // Explicitly marked as Body
                $bodyParams[] = [
                    'name' => $param['name'],
                    'schema' => $this->phpTypeToSchemaIR($param['type'], $param['nullable']),
                    'required' => $param['required']
                ];
            } elseif (in_array($route['method'], ['GET', 'HEAD', 'DELETE'])) {
                // Default: GET/HEAD/DELETE use query
                $queryParams[] = new ParamIR(
                    $param['name'],
                    $this->phpTypeToSchemaIR($param['type'], $param['nullable']),
                    $param['required']
                );
            } else {
                // Default: POST/PUT/PATCH use body
                $bodyParams[] = [
                    'name' => $param['name'],
                    'schema' => $this->phpTypeToSchemaIR($param['type'], $param['nullable']),
                    'required' => $param['required']
                ];
            }
        }

        // Build request body
        $body = null;
        if ($bodyParams !== []) {
            $properties = [];
            foreach ($bodyParams as $param) {
                $properties[$param['name']] = [
                    'schema' => $param['schema'],
                    'required' => $param['required']
                ];
            }

            $body = [
                'schema' => SchemaIR::object($properties),
                'required' => true
            ];
        }

        // Build responses
        $responses = $this->buildResponses($methodReflection);

        // Build tags
        $tags = $this->getTags($reflection, $methodReflection);
        if ($tags === []) {
            $tags = ['API'];
        }

        // Generate operation ID
        $operationId = $this->generateOperationId($controllerClass, $route['method'], $route['path']);

        // Detect envelope from attributes or default to true
        $envelope = $this->shouldEnvelope($reflection, $methodReflection)
            ? ['type' => 'Envelope', 'dataRef' => 'T']
            : null;

        return new OperationIR(
            operationId: $operationId,
            method: $route['method'],
            path: $route['path'],
            tags: $tags,
            pathParams: $pathParams,
            queryParams: $queryParams,
            body: $body,
            responses: $responses,
            envelope: $envelope,
            errorRef: 'ErrorResponse'
        );
    }

    private function getControllerMethodName(string $httpMethod): string
    {
        return match ($httpMethod) {
            'GET' => 'get',
            'POST' => 'post',
            'PUT' => 'put',
            'PATCH' => 'patch',
            'DELETE' => 'delete',
            'OPTIONS' => 'options',
            'HEAD' => 'head',
            default => 'action'
        };
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function getControllerParameters(ReflectionClass $reflection): array
    {
        $parameters = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $type = $prop->getType();
            $typeName = $type ? $type->__toString() : 'mixed';

            // Only include scalar types and arrays as parameters
            if ($this->isScalarOrArrayType($typeName)) {
                // Check for explicit Query/Body attributes
                $queryAttr = $prop->getAttributes(Query::class);
                $bodyAttr = $prop->getAttributes(Body::class);
                $rulesAttr = $prop->getAttributes(Rules::class);

                $isQuery = !empty($queryAttr);
                $isBody = !empty($bodyAttr);

                // Determine required status
                $required = false;
                if ($rulesAttr) {
                    $rules = $rulesAttr[0]->newInstance();
                    $required = $rules->isRequired();
                } elseif ($queryAttr) {
                    $required = $queryAttr[0]->newInstance()->required;
                } elseif ($bodyAttr) {
                    $required = $bodyAttr[0]->newInstance()->required;
                } else {
                    $required = !($type && $type->allowsNull()) && !$prop->hasDefaultValue();
                }

                $parameters[] = [
                    'name' => $prop->getName(),
                    'type' => $typeName,
                    'nullable' => $type && $type->allowsNull(),
                    'hasDefault' => $prop->hasDefaultValue(),
                    'default' => $prop->hasDefaultValue() ? $prop->getDefaultValue() : null,
                    'required' => $required,
                    'isQuery' => $isQuery,
                    'isBody' => $isBody,
                    'property' => $prop,
                ];
            }
        }

        return $parameters;
    }

    private function isScalarOrArrayType(string $typeName): bool
    {
        if (str_starts_with($typeName, '?')) {
            $typeName = substr($typeName, 1);
        }

        if (str_contains($typeName, '|')) {
            $types = explode('|', $typeName);
            $nonNullTypes = array_filter($types, fn($t): bool => trim($t) !== 'null');
            if (count($nonNullTypes) === 1) {
                $typeName = trim($nonNullTypes[0]);
            }
        }

        return in_array($typeName, [
            'string',
            'int',
            'integer',
            'float',
            'double',
            'bool',
            'boolean',
            'array',
            'mixed'
        ]);
    }

    private function phpTypeToSchemaIR(string $phpType, bool $nullable = false): SchemaIR
    {
        // Handle nullable prefix
        if (str_starts_with($phpType, '?')) {
            $nullable = true;
            $phpType = substr($phpType, 1);
        }

        // Handle array types
        if (str_ends_with($phpType, '[]')) {
            $itemType = substr($phpType, 0, -2);
            return SchemaIR::array($this->phpTypeToSchemaIR($itemType, false));
        }

        // Check if it's a class
        if (class_exists($phpType)) {
            $this->resolveModel($phpType);
            return SchemaIR::ref($this->getShortClassName($phpType));
        }

        // Map PHP primitives
        return match ($phpType) {
            'int', 'integer' => SchemaIR::primitive('integer', $nullable),
            'float', 'double', 'number' => SchemaIR::primitive('number', $nullable),
            'string' => SchemaIR::primitive('string', $nullable),
            'bool', 'boolean' => SchemaIR::primitive('boolean', $nullable),
            'array' => SchemaIR::array(SchemaIR::unknown()), // Fix: array without item type → unknown[]
            default => SchemaIR::unknown()
        };
    }

    private function buildResponses(ReflectionMethod $method): array
    {
        $responses = [];

        $attributes = $method->getAttributes(ResponseType::class);
        foreach ($attributes as $attr) {
            /** @var ResponseType $responseType */
            $responseType = $attr->newInstance();
            $shapeInfo = $responseType->getShapeInfo();

            $responses[] = [
                'status' => $responseType->status,
                'schema' => $this->shapeInfoToSchemaIR($shapeInfo)
            ];
        }

        // Default 200 response if none defined
        if ($responses === []) {
            $responses[] = [
                'status' => 200,
                'schema' => SchemaIR::unknown()
            ];
        }

        return $responses;
    }

    private function shapeInfoToSchemaIR(array $shapeInfo): SchemaIR
    {
        switch ($shapeInfo['type']) {
            case 'null':
            default:
                return SchemaIR::unknown(); // Fix: null should be unknown, not nullable string

            case 'class':
                $this->resolveModel($shapeInfo['class']);
                return SchemaIR::ref($this->getShortClassName($shapeInfo['class']));

            case 'array':
                if (class_exists($shapeInfo['items'])) {
                    $this->resolveModel($shapeInfo['items']);
                    return SchemaIR::array(SchemaIR::ref($this->getShortClassName($shapeInfo['items'])));
                }

                return SchemaIR::array($this->phpTypeToSchemaIR($shapeInfo['items']));

            case 'object':
                $properties = [];
                foreach ($shapeInfo['properties'] as $propName => $propType) {
                    $properties[$propName] = [
                        'schema' => $this->phpTypeToSchemaIR($propType),
                        'required' => true
                    ];
                }

                return SchemaIR::object($properties);
        }
    }

    private function resolveModel(string $className): void
    {
        if (isset($this->processedModels[$className])) {
            return;
        }

        if (!class_exists($className)) {
            return;
        }

        $this->processedModels[$className] = true;

        $reflection = new ReflectionClass($className);
        $properties = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $type = $prop->getType();
            $typeName = $type ? $type->__toString() : 'mixed';
            $nullable = $type && $type->allowsNull();

            $properties[$prop->getName()] = [
                'schema' => $this->phpTypeToSchemaIR($typeName, $nullable),
                'required' => !$nullable && !$prop->hasDefaultValue()
            ];
        }

        $this->models[$this->getShortClassName($className)] = [
            'fullName' => $className,
            'schema' => SchemaIR::object($properties)
        ];
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function getTags(ReflectionClass $class, ReflectionMethod $method): array
    {
        $tags = [];

        $classAttrs = $class->getAttributes(Tag::class);
        foreach ($classAttrs as $attr) {
            $tags = array_merge($tags, $attr->newInstance()->tags);
        }

        $methodAttrs = $method->getAttributes(Tag::class);
        foreach ($methodAttrs as $attr) {
            $tags = array_merge($tags, $attr->newInstance()->tags);
        }

        return array_unique($tags);
    }

    private function generateOperationId(string $controllerClass, string $method, string $path): string
    {
        $controller = str_replace('Controller', '', $this->getShortClassName($controllerClass));
        $method = strtolower($method);
        $pathSuffix = $this->getPathSuffix($path);

        return $method . $controller . $pathSuffix;
    }

    private function getShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }

    private function getPathSuffix(string $path): string
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        if (empty($matches[1])) {
            return '';
        }

        $suffixParts = [];
        foreach ($matches[1] as $param) {
            $suffixParts[] = 'By' . ucfirst($param);
        }

        return implode('', $suffixParts);
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function shouldEnvelope(ReflectionClass $class, ReflectionMethod $method): bool
    {
        // Check method-level attribute first
        $methodAttrs = $method->getAttributes(Enveloped::class);
        if ($methodAttrs !== []) {
            return $methodAttrs[0]->newInstance()->enabled;
        }

        // Check class-level attribute
        $classAttrs = $class->getAttributes(Enveloped::class);
        if ($classAttrs !== []) {
            return $classAttrs[0]->newInstance()->enabled;
        }

        // Default: envelope enabled
        return true;
    }
}
