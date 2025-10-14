<?php

declare(strict_types=1);

namespace BaseApi\OpenApi;

use BaseApi\App;
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\Http\Attributes\Tag;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionUnionType;

class OpenApiGenerator
{
    private array $routes = [];

    private array $dtoSchemas = [];

    public function generate(): array
    {
        // Step 1: Load and analyze routes
        $this->loadRoutes();

        // Step 2: Reflect controllers and build component graph
        $this->analyzeControllers();

        // Step 3: Resolve DTOs recursively
        $this->resolveDtos();

        // Step 4: Generate OpenAPI specification
        return $this->generateOpenApiSpec();
    }

    public function generateToFile(string $filePath): void
    {
        $spec = $this->generate();
        $output = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->ensureDirectoryExists(dirname($filePath));

        if (file_put_contents($filePath, $output) === false) {
            throw new Exception('Failed to write OpenAPI spec to ' . $filePath);
        }
    }

    private function loadRoutes(): void
    {
        $routesFile = App::basePath('routes/api.php');

        if (!file_exists($routesFile)) {
            throw new Exception('Routes file not found: ' . $routesFile);
        }

        // Parse the routes file manually to avoid conflicts with existing App class
        $this->parseRoutesFile($routesFile);
    }

    private function parseRoutesFile(string $routesFile): void
    {
        $content = file_get_contents($routesFile);
        $routes = [];

        // Simple regex parsing of router calls
        // Match $router->get('/path', [...]);
        $pattern = '/\$router->(get|post|delete|put|patch)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[(.*?)\]\s*,?\s*\);/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $method = strtoupper($match[1]);
                $path = $match[2];
                $pipelineContent = $match[3];

                // Extract pipeline classes
                $pipeline = [];
                $classPattern = '/([A-Za-z\\\\]+::class)/';
                if (preg_match_all($classPattern, $pipelineContent, $classMatches)) {
                    foreach ($classMatches[1] as $classRef) {
                        $className = str_replace('::class', '', $classRef);
                        // Convert short class names to full names by checking imports
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
        // Extract use statements to resolve class names
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

        return $shortName; // Fallback to short name if not found
    }

    private function analyzeControllers(): void
    {
        foreach ($this->routes as &$route) {
            $pipeline = $route['pipeline'];
            $controllerClass = end($pipeline); // Last element should be controller

            if (!class_exists($controllerClass)) {
                continue;
            }

            $route['controller'] = $this->analyzeController($controllerClass, $route['method']);
        }
    }

    private function analyzeController(string $controllerClass, string $method): array
    {
        $reflection = new ReflectionClass($controllerClass);

        // Determine method name
        $methodName = match ($method) {
            'GET' => 'get',
            'POST' => 'post',
            'PUT' => 'put',
            'PATCH' => 'patch',
            'DELETE' => 'delete',
            default => 'action'
        };

        if (!$reflection->hasMethod($methodName)) {
            $methodName = 'action';
        }

        if (!$reflection->hasMethod($methodName)) {
            throw new Exception(sprintf('No suitable method found in %s for %s', $controllerClass, $method));
        }

        $methodReflection = $reflection->getMethod($methodName);

        return [
            'class' => $controllerClass,
            'method' => $methodName,
            'properties' => $this->getControllerProperties($reflection),
            'parameters' => $this->getAllParameters($reflection, $methodReflection),
            'responseTypes' => $this->getResponseTypes($methodReflection),
            'tags' => $this->getTags($reflection, $methodReflection),
        ];
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function getControllerProperties(ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $type = $prop->getType();
            $properties[] = [
                'name' => $prop->getName(),
                'type' => $type ? $type->__toString() : 'mixed',
                'nullable' => $type && $type->allowsNull(),
                'hasDefault' => $prop->hasDefaultValue(),
                'default' => $prop->hasDefaultValue() ? $prop->getDefaultValue() : null,
            ];
        }

        return $properties;
    }

    private function getMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $parameters[] = [
                'name' => $param->getName(),
                'type' => $type ? $type->__toString() : 'mixed',
                'nullable' => $type && $type->allowsNull(),
                'hasDefault' => $param->isDefaultValueAvailable(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                'isOptional' => $param->isOptional(),
            ];
        }

        return $parameters;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function getAllParameters(ReflectionClass $reflection, ReflectionMethod $method): array
    {
        $parameters = [];

        // 1. Add method parameters
        $parameters = array_merge($parameters, $this->getMethodParameters($method));

        // 2. Add scalar public properties (API parameters)
        $scalarProperties = $this->getScalarProperties($reflection);

        return array_merge($parameters, $scalarProperties);
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function getScalarProperties(ReflectionClass $reflection): array
    {
        $parameters = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $type = $prop->getType();
            $typeName = $type ? $type->__toString() : 'mixed';

            // Only include scalar types as API parameters
            if ($this->isScalarType($typeName)) {
                $parameters[] = [
                    'name' => $prop->getName(),
                    'type' => $typeName,
                    'nullable' => $type && $type->allowsNull(),
                    'hasDefault' => $prop->hasDefaultValue(),
                    'default' => $prop->hasDefaultValue() ? $prop->getDefaultValue() : null,
                    'isOptional' => $prop->hasDefaultValue() || ($type && $type->allowsNull()),
                ];
            }
        }

        return $parameters;
    }

    private function isScalarType(string $typeName): bool
    {
        // Handle nullable types by removing the leading ?
        if (str_starts_with($typeName, '?')) {
            $typeName = substr($typeName, 1);
        }

        // Handle union types (like string|null)
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

    private function getResponseTypes(ReflectionMethod $method): array
    {
        $responseTypes = [];

        $attributes = $method->getAttributes(ResponseType::class);
        foreach ($attributes as $attr) {
            $responseTypes[] = $attr->newInstance();
        }

        return $responseTypes;
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function getTags(ReflectionClass $class, ReflectionMethod $method): array
    {
        $tags = [];

        // Class-level tags
        $classAttrs = $class->getAttributes(Tag::class);
        foreach ($classAttrs as $attr) {
            $tags = array_merge($tags, $attr->newInstance()->tags);
        }

        // Method-level tags
        $methodAttrs = $method->getAttributes(Tag::class);
        foreach ($methodAttrs as $attr) {
            $tags = array_merge($tags, $attr->newInstance()->tags);
        }

        return array_unique($tags);
    }

    private function resolveDtos(): void
    {
        // Collect all DTO references from routes
        $dtoClasses = [];

        foreach ($this->routes as $route) {
            if (!isset($route['controller'])) {
                continue;
            }

            foreach ($route['controller']['responseTypes'] as $responseType) {
                $dtoClasses = array_merge($dtoClasses, $responseType->getClassReferences());
            }
        }

        // Resolve each DTO recursively
        foreach (array_unique($dtoClasses) as $dtoClass) {
            if (!isset($this->dtoSchemas[$dtoClass])) {
                $this->resolveDto($dtoClass);
            }
        }
    }

    private function resolveDto(string $className): array
    {
        if (isset($this->dtoSchemas[$className])) {
            return $this->dtoSchemas[$className];
        }

        if (!class_exists($className)) {
            throw new Exception('DTO class not found: ' . $className);
        }

        $reflection = new ReflectionClass($className);
        $schema = [
            'name' => $className,
            'shortName' => $reflection->getShortName(),
            'properties' => []
        ];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $propType = $prop->getType();
            $typeInfo = $this->getTypeInfo($propType);

            $schema['properties'][] = [
                'name' => $prop->getName(),
                'type' => $typeInfo['type'],
                'nullable' => $typeInfo['nullable'],
                'isArray' => $typeInfo['isArray'],
                'className' => $typeInfo['className'] ?? null,
            ];

            // Recursively resolve referenced DTOs
            if (isset($typeInfo['className']) && !isset($this->dtoSchemas[$typeInfo['className']])) {
                $this->resolveDto($typeInfo['className']);
            }
        }

        $this->dtoSchemas[$className] = $schema;
        return $schema;
    }

    private function getTypeInfo(mixed $type): array
    {
        if (!$type) {
            return ['type' => 'mixed', 'nullable' => true, 'isArray' => false];
        }

        $typeName = $type->__toString();
        $nullable = $type->allowsNull();

        // Handle union types (mainly for nullable)
        if ($type instanceof ReflectionUnionType) {
            $types = $type->getTypes();
            $nonNullTypes = array_filter($types, fn($t): bool => $t->__toString() !== 'null');

            if (count($nonNullTypes) === 1) {
                $typeName = $nonNullTypes[0]->__toString();
                $nullable = count($types) > 1; // null was filtered out
            }
        }

        // Handle array types
        $isArray = str_ends_with((string) $typeName, '[]') || $typeName === 'array';
        if ($isArray && str_ends_with((string) $typeName, '[]')) {
            $typeName = substr((string) $typeName, 0, -2);
        }

        // Determine if this is a class reference
        $className = null;
        if (class_exists($typeName)) {
            $className = $typeName;
            $type = 'object';
        } else {
            $type = match ($typeName) {
                'int' => 'integer',
                'float' => 'number',
                'string' => 'string',
                'bool' => 'boolean',
                'array' => 'array',
                default => 'mixed'
            };
        }

        return [
            'type' => $type,
            'nullable' => $nullable,
            'isArray' => $isArray,
            'className' => $className,
        ];
    }

    private function generateOpenApiSpec(): array
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'BaseApi',
                'version' => '1.0.0',
                'description' => 'Generated API documentation from BaseApi controllers'
            ],
            'paths' => [],
            'components' => [
                'schemas' => []
            ]
        ];

        // Add server info if available
        if (isset($_ENV['APP_URL'])) {
            $spec['servers'] = [['url' => $_ENV['APP_URL']]];
        }

        // Generate paths
        foreach ($this->routes as $route) {
            if (!isset($route['controller'])) {
                continue;
            }

            $path = $this->convertPathToOpenApi($route['path']);
            $method = strtolower((string) $route['method']);

            $spec['paths'][$path][$method] = $this->generateOpenApiOperation($route);
        }

        // Generate component schemas
        foreach ($this->dtoSchemas as $schema) {
            $spec['components']['schemas'][$schema['shortName']] = $this->generateOpenApiSchema($schema);
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
        $spec['components']['responses'] = [
            'BadRequest' => [
                'description' => 'Bad Request',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            'ServerError' => [
                'description' => 'Internal Server Error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ]
        ];

        return $spec;
    }

    private function convertPathToOpenApi(string $path): string
    {
        return preg_replace('/\{(\w+)\}/', '{$1}', $path);
    }

    private function generateOpenApiOperation(array $route): array
    {
        $controller = $route['controller'];
        $method = strtolower((string) $route['method']);

        $operation = [
            'summary' => $this->generateOperationSummary($route),
            'operationId' => $this->generateOperationId($route),
            'tags' => $controller['tags'] ?: ['API'],
            'parameters' => [],
            'responses' => []
        ];

        // Add path parameters
        preg_match_all('/\{(\w+)\}/', (string) $route['path'], $matches);
        foreach ($matches[1] as $paramName) {
            $paramType = $this->getPathParamType($controller['parameters'], $paramName);
            $operation['parameters'][] = [
                'name' => $paramName,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => $paramType]
            ];
        }

        // Add query parameters (GET) or request body (POST, PUT, PATCH)
        if ($method === 'get') {
            $operation['parameters'] = array_merge(
                $operation['parameters'],
                $this->generateQueryParameters($controller['parameters'], $matches[1])
            );
        } elseif (in_array($method, ['post', 'put', 'patch'])) {
            $bodySchema = $this->generateRequestBodySchema($controller['parameters'], $matches[1]);
            if (!empty($bodySchema['properties'])) {
                $operation['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => $bodySchema
                        ]
                    ]
                ];
            }
        }

        // Add success responses from ResponseType attributes
        if (!empty($controller['responseTypes'])) {
            foreach ($controller['responseTypes'] as $responseType) {
                $status = (string)$responseType->status;
                $operation['responses'][$status] = $this->generateOpenApiResponse($responseType);
            }
        } else {
            // Default 200 response
            $operation['responses']['200'] = [
                'description' => 'Success',
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'object']
                    ]
                ]
            ];
        }

        // Add standard error responses
        $operation['responses']['400'] = ['$ref' => '#/components/responses/BadRequest'];
        $operation['responses']['500'] = ['$ref' => '#/components/responses/ServerError'];

        return $operation;
    }

    private function generateOpenApiResponse(ResponseType $responseType): array
    {
        $shapeInfo = $responseType->getShapeInfo();

        return [
            'description' => $responseType->when ? ucfirst($responseType->when) : 'Success',
            'content' => [
                'application/json' => [
                    'schema' => $this->convertShapeToOpenApiSchema($shapeInfo)
                ]
            ]
        ];
    }

    private function convertShapeToOpenApiSchema(array $shapeInfo): array
    {
        switch ($shapeInfo['type']) {
            case 'null':
                return ['type' => 'null'];

            case 'class':
                return ['$ref' => '#/components/schemas/' . $this->getShortClassName($shapeInfo['class'])];

            case 'array':
                if (class_exists($shapeInfo['items'])) {
                    return [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/' . $this->getShortClassName($shapeInfo['items'])]
                    ];
                }

                return [
                    'type' => 'array',
                    'items' => $this->phpTypeToOpenApiType($shapeInfo['items'])
                ];

            case 'object':
                $properties = [];
                foreach ($shapeInfo['properties'] as $propName => $propType) {
                    $properties[$propName] = $this->phpTypeToOpenApiType($propType);
                }

                return [
                    'type' => 'object',
                    'properties' => $properties
                ];

            default:
                return ['type' => 'object'];
        }
    }

    private function generateOpenApiSchema(array $schema): array
    {
        $properties = [];
        $required = [];

        foreach ($schema['properties'] as $prop) {
            $propSchema = $this->getPropertyOpenApiSchema($prop);
            $properties[$prop['name']] = $propSchema;

            if (!$prop['nullable'] && !isset($prop['hasDefault'])) {
                $required[] = $prop['name'];
            }
        }

        $result = [
            'type' => 'object',
            'properties' => $properties
        ];

        if ($required !== []) {
            $result['required'] = $required;
        }

        return $result;
    }

    private function getPropertyOpenApiSchema(array $prop): array
    {
        if ($prop['className']) {
            $schema = ['$ref' => '#/components/schemas/' . $this->getShortClassName($prop['className'])];
        } else {
            $schema = $this->phpTypeToOpenApiType($prop['type']);
        }

        if ($prop['isArray']) {
            return [
                'type' => 'array',
                'items' => $schema
            ];
        }

        return $schema;
    }

    private function phpTypeToOpenApiType(string $phpType): array
    {
        return match ($phpType) {
            'int', 'integer' => ['type' => 'integer'],
            'float', 'number' => ['type' => 'number'],
            'string' => ['type' => 'string'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            default => ['type' => 'string']
        };
    }

    private function generateOperationSummary(array $route): string
    {
        $controller = $this->getShortClassName($route['controller']['class']);
        $method = $route['method'];
        return sprintf('%s %s', $method, $controller);
    }

    private function generateOperationId(array $route): string
    {
        $controller = str_replace('Controller', '', $this->getShortClassName($route['controller']['class']));
        $method = strtolower((string) $route['method']);
        $pathSuffix = $this->getPathSuffix($route['path']);

        return $method . $controller . $pathSuffix;
    }

    private function getShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }

    private function getPathSuffix(string $path): string
    {
        // Extract path parameters like {id}, {userId}, etc.
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        if (empty($matches[1])) {
            return '';
        }

        // Convert parameter names to PascalCase and join
        $suffixParts = [];
        foreach ($matches[1] as $param) {
            $suffixParts[] = 'By' . ucfirst($param);
        }

        return implode('', $suffixParts);
    }

    private function getPathParamType(array $parameters, string $paramName): string
    {
        foreach ($parameters as $param) {
            if ($param['name'] === $paramName) {
                return match ($param['type']) {
                    'int' => 'integer',
                    'float' => 'number',
                    'bool' => 'boolean',
                    default => 'string'
                };
            }
        }

        return 'string';
    }

    private function generateQueryParameters(array $parameters, array $pathParamNames): array
    {
        $params = [];
        foreach ($parameters as $param) {
            if (in_array($param['name'], $pathParamNames)) {
                continue;
            }

            $params[] = [
                'name' => $param['name'],
                'in' => 'query',
                'required' => !$param['nullable'] && !$param['hasDefault'],
                'schema' => $this->phpTypeToOpenApiType($param['type'])
            ];
        }

        return $params;
    }

    private function generateRequestBodySchema(array $parameters, array $pathParamNames): array
    {
        $schema = [
            'type' => 'object',
            'properties' => []
        ];

        foreach ($parameters as $param) {
            if (in_array($param['name'], $pathParamNames)) {
                continue;
            }

            $schema['properties'][$param['name']] = $this->phpTypeToOpenApiType($param['type']);
        }

        return $schema;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new Exception('Failed to create directory: ' . $directory);
        }
    }
}
