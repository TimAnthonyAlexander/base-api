<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use Exception;
use ReflectionUnionType;
use BaseApi\Console\Command;
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\Http\Attributes\Tag;
use BaseApi\App;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class TypesGenerateCommand implements Command
{
    private array $routes = [];

    private array $dtoSchemas = [];

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

        echo "Generating types from BaseApi controllers and routes...\n";

        try {
            // Step 1: Load and analyze routes
            echo "📖 Scanning routes...\n";
            $this->loadRoutes();

            // Step 2: Reflect controllers and build component graph
            echo "🔍 Reflecting controllers...\n";
            $this->analyzeControllers();

            // Step 3: Resolve DTOs recursively
            echo "📋 Resolving DTOs...\n";
            $this->resolveDtos();

            // Step 4: Generate OpenAPI if requested
            if (isset($options['out-openapi'])) {
                echo "🌐 Generating OpenAPI spec...\n";
                $this->generateOpenApi($options);
            }

            // Step 5: Generate TypeScript if requested
            if (isset($options['out-ts'])) {
                echo "🔷 Generating TypeScript definitions...\n";
                $this->generateTypeScript($options);
            }

            echo "✅ Type generation completed!\n";
            return 0;
        } catch (Exception $exception) {
            echo "❌ Error: " . $exception->getMessage() . "\n";
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
  php bin/console types:generate [options]

Options:
  --out-ts=PATH          Output path for TypeScript definitions (default: types.ts)
  --out-openapi=PATH     Output path for OpenAPI specification (default: openapi.json)
  --format=FORMAT        OpenAPI format: json (default) or yaml
  --schemas-dir=PATH     Output directory for individual JSON schemas
  --help, -h             Show this help message

Examples:
  php bin/console types:generate
  php bin/console types:generate --out-ts=web/types/baseapi.d.ts
  php bin/console types:generate --out-openapi=storage/openapi.json
  php bin/console types:generate --out-ts=types.d.ts --out-openapi=api.json --format=yaml

HELP;
    }

    private function loadRoutes(): void
    {
        $routesFile = App::basePath('routes/api.php');

        if (!file_exists($routesFile)) {
            throw new Exception('Routes file not found: ' . $routesFile);
        }

        // Create a mock App class to capture router calls
        $routes = [];

        // Mock the App::router() method by creating a temporary class
        $mockRouter = new class($routes) {
            private array $routes;

            public function __construct(array &$routes)
            {
                $this->routes = &$routes;
            }

            public function get(string $path, array $pipeline): void
            {
                $this->routes[] = ['method' => 'GET', 'path' => $path, 'pipeline' => $pipeline];
            }

            public function post(string $path, array $pipeline): void
            {
                $this->routes[] = ['method' => 'POST', 'path' => $path, 'pipeline' => $pipeline];
            }

            public function delete(string $path, array $pipeline): void
            {
                $this->routes[] = ['method' => 'DELETE', 'path' => $path, 'pipeline' => $pipeline];
            }
        };

        // Create a mock App class
        $originalAppExists = class_exists(App::class, false);
        if (!$originalAppExists) {
            // If App isn't loaded yet, we need to ensure we can mock it
            $mockRouterRef = &$mockRouter; // Create reference for closure
            eval('
            namespace BaseApi {
                class App {
                    private static $mockRouter;
                    public static function setMockRouter($router) { self::$mockRouter = $router; }
                    public static function router() { return self::$mockRouter; }
                }
            }
            ');
            // Now the mock App class exists, we can call setMockRouter
            /** @phpstan-ignore-next-line */
            App::setMockRouter($mockRouterRef);
        } else {
            // If App is already loaded, we need to work around it
            // Store original router method (this is tricky with static methods)
            // For now, let's use a different approach - parse the file manually
            $this->parseRoutesFile($routesFile);
            return;
        }

        require $routesFile;
        $this->routes = $routes;

        echo "   Found " . count($this->routes) . " routes\n";
    }

    private function parseRoutesFile(string $routesFile): void
    {
        $content = file_get_contents($routesFile);
        $routes = [];

        // Simple regex parsing of router calls
        // Match $router->get('/path', [...]);
        $pattern = '/\$router->(get|post|delete)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[(.*?)\]\s*,?\s*\);/s';

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
        echo "   Found " . count($this->routes) . " routes\n";
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
                echo sprintf('   ⚠️  Controller not found: %s%s', $controllerClass, PHP_EOL);
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

    private function getAllParameters(ReflectionClass $reflection, ReflectionMethod $method): array
    {
        $parameters = [];

        // 1. Add method parameters
        $parameters = array_merge($parameters, $this->getMethodParameters($method));

        // 2. Add scalar public properties (API parameters)
        $scalarProperties = $this->getScalarProperties($reflection);

        return array_merge($parameters, $scalarProperties);
    }

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
            $nonNullTypes = array_filter($types, fn($t): bool => trim((string) $t) !== 'null');
            if (count($nonNullTypes) === 1) {
                $typeName = trim($nonNullTypes[0]);
            }
        }

        return in_array($typeName, [
            'string', 'int', 'integer', 'float', 'double', 'bool', 'boolean', 'array', 'mixed'
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

        echo "   Resolved " . count($this->dtoSchemas) . " DTOs\n";
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

    private function getTypeInfo($type): array
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

    private function generateOpenApi(array $options): void
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

        // Write output
        $output = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->ensureDirectoryExists(dirname((string) $options['out-openapi']));

        if (file_put_contents($options['out-openapi'], $output) === false) {
            throw new Exception('Failed to write OpenAPI spec to ' . $options['out-openapi']);
        }

        echo sprintf('   📄 OpenAPI spec written to %s%s', $options['out-openapi'], PHP_EOL);
    }

    private function generateTypeScript(array $options): void
    {
        $ts = [];

        // Header and base types
        $ts[] = "// Generated TypeScript definitions for BaseApi";
        $ts[] = "// Do not edit manually - regenerate with: php bin/console types:generate";
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

        // Generate DTO interfaces
        foreach ($this->dtoSchemas as $schema) {
            $ts = array_merge($ts, $this->generateTypeScriptInterface($schema));
            $ts[] = "";
        }

        // Generate request/response types for each route
        foreach ($this->routes as $route) {
            if (!isset($route['controller'])) {
                continue;
            }

            $ts = array_merge($ts, $this->generateTypeScriptOperation($route));
            $ts[] = "";
        }

        $output = implode("\n", $ts);

        $this->ensureDirectoryExists(dirname((string) $options['out-ts']));

        if (file_put_contents($options['out-ts'], $output) === false) {
            throw new Exception('Failed to write TypeScript definitions to ' . $options['out-ts']);
        }

        echo sprintf('   📘 TypeScript definitions written to %s%s', $options['out-ts'], PHP_EOL);
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

        // Add query parameters (GET) or request body (POST)
        if ($method === 'get') {
            $operation['parameters'] = array_merge(
                $operation['parameters'],
                $this->generateQueryParameters($controller['parameters'], $matches[1])
            );
        } elseif ($method === 'post') {
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
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'object']
                            ]
                        ]
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
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => $this->convertShapeToOpenApiSchema($shapeInfo)
                        ]
                    ]
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

    private function generateTypeScriptInterface(array $schema): array
    {
        $lines = [];
        $lines[] = sprintf('export interface %s {', $schema['shortName']);

        foreach ($schema['properties'] as $prop) {
            $tsType = $this->phpTypeToTypeScript($prop);
            $optional = $prop['nullable'] ? '?' : '';
            $lines[] = sprintf('  %s%s: %s;', $prop['name'], $optional, $tsType);
        }

        $lines[] = "}";
        return $lines;
    }

    private function phpTypeToTypeScript(array $prop): string
    {
        $baseType = $prop['className']
            ? $this->getShortClassName($prop['className'])
            : $this->phpTypeToTsType($prop['type']);

        if ($prop['isArray']) {
            $baseType .= '[]';
        }

        if ($prop['nullable']) {
            $baseType .= ' | null';
        }

        return $baseType;
    }

    private function phpTypeToTsType(string $phpType): string
    {
        return match ($phpType) {
            'integer', 'int' => 'number',
            'number', 'float' => 'number',
            'string' => 'string',
            'boolean', 'bool' => 'boolean',
            'array' => 'any[]',
            default => 'any'
        };
    }

    private function generateTypeScriptOperation(array $route): array
    {
        $controller = $route['controller'];
        $operationName = $this->generateOperationName($route);
        $lines = [];

        // Generate path parameters interface
        $pathParams = $this->getPathParameters($route['path'], $controller['parameters']);
        if ($pathParams !== []) {
            $lines[] = sprintf('export interface %sRequestPath {', $operationName);
            foreach ($pathParams as $param) {
                $tsType = $this->phpTypeToTsType($param['type']);
                $lines[] = sprintf('  %s: %s;', $param['name'], $tsType);
            }

            $lines[] = "}";
            $lines[] = "";
        }

        // Generate query/body parameters interface
        $bodyParams = $this->getBodyParameters($route, $controller['parameters']);
        if ($bodyParams !== []) {
            $interfaceType = $route['method'] === 'GET' ? 'Query' : 'Body';
            $lines[] = sprintf('export interface %sRequest%s {', $operationName, $interfaceType);
            foreach ($bodyParams as $param) {
                $tsType = $this->phpTypeToTsType($param['type']);
                $optional = $param['nullable'] || $param['hasDefault'] ? '?' : '';
                $lines[] = sprintf('  %s%s: %s;', $param['name'], $optional, $tsType);
            }

            $lines[] = "}";
            $lines[] = "";
        }

        // Generate response types
        if (!empty($controller['responseTypes'])) {
            $responseUnions = [];
            foreach ($controller['responseTypes'] as $responseType) {
                $shapeInfo = $responseType->getShapeInfo();
                $tsType = $this->convertShapeToTypeScript($shapeInfo);
                $responseUnions[] = sprintf('Envelope<%s>', $tsType);
            }

            $lines[] = sprintf('export type %sResponse = ', $operationName) . implode(' | ', $responseUnions) . ";";
        } else {
            $lines[] = sprintf('export type %sResponse = Envelope<any>;', $operationName);
        }

        return $lines;
    }

    private function convertShapeToTypeScript(array $shapeInfo): string
    {
        switch ($shapeInfo['type']) {
            case 'null':
                return 'null';

            case 'class':
                return $this->getShortClassName($shapeInfo['class']);

            case 'array':
                if (class_exists($shapeInfo['items'])) {
                    return $this->getShortClassName($shapeInfo['items']) . '[]';
                }

                return $this->phpTypeToTsType($shapeInfo['items']) . '[]';

            case 'object':
                $props = [];
                foreach ($shapeInfo['properties'] as $propName => $propType) {
                    $tsType = $this->phpTypeToTsType($propType);
                    $props[] = sprintf('%s: %s', $propName, $tsType);
                }

                return '{ ' . implode('; ', $props) . ' }';

            default:
                return 'any';
        }
    }

    private function getShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
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

    private function generateOperationName(array $route): string
    {
        $controller = str_replace('Controller', '', $this->getShortClassName($route['controller']['class']));
        $method = ucfirst(strtolower((string) $route['method']));

        // Add path parameters to make operation names unique
        $pathSuffix = $this->getPathSuffix($route['path']);

        return $method . $controller . $pathSuffix;
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

    private function getPathParameters(string $path, array $parameters): array
    {
        $params = [];
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        foreach ($matches[1] as $paramName) {
            foreach ($parameters as $param) {
                if ($param['name'] === $paramName) {
                    $params[] = $param;
                    break;
                }
            }
        }

        return $params;
    }

    private function getBodyParameters(array $route, array $parameters): array
    {
        $pathParamNames = [];
        preg_match_all('/\{(\w+)\}/', (string) $route['path'], $matches);
        if (isset($matches[1]) && $matches[1] !== []) {
            $pathParamNames = $matches[1];
        }

        $bodyParams = [];
        foreach ($parameters as $param) {
            if (!in_array($param['name'], $pathParamNames)) {
                $bodyParams[] = $param;
            }
        }

        return $bodyParams;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new Exception('Failed to create directory: ' . $directory);
        }
    }
}
