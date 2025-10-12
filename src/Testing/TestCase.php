<?php

namespace BaseApi\Testing;

use Override;
use RuntimeException;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use BaseApi\App;
use BaseApi\Http\Request;
use BaseApi\Http\JsonResponse;
use BaseApi\Controllers\Controller;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case with convenient HTTP testing methods
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected static bool $appBooted = false;
    
    /**
     * Boot the application before tests
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!self::$appBooted) {
            $this->bootApplication();
            self::$appBooted = true;
        }
    }
    
    /**
     * Boot the BaseAPI application
     */
    protected function bootApplication(): void
    {
        $basePath = $this->getBasePath();
        
        // App::boot() handles already-booted state internally
        App::boot($basePath);
    }
    
    /**
     * Get the base path for the application
     */
    protected function getBasePath(): string
    {
        // Try to find base path by looking for public/index.php
        $currentDir = getcwd();
        
        // Common paths to check
        $paths = [
            $currentDir,
            dirname($currentDir),
            dirname(__DIR__, 4), // From vendor/baseapi/framework/src/Testing
            dirname(__DIR__, 2), // From baseapi/src/Testing
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path . '/public/index.php')) {
                return $path;
            }
        }
        
        return $currentDir;
    }
    
    /**
     * Make a GET request
     */
    protected function get(string $path, array $query = [], array $headers = []): TestResponse
    {
        return $this->makeRequest('GET', $path, $query, [], $headers);
    }
    
    /**
     * Make a POST request
     */
    protected function post(string $path, array $data = [], array $headers = []): TestResponse
    {
        return $this->makeRequest('POST', $path, [], $data, $headers);
    }
    
    /**
     * Make a PUT request
     */
    protected function put(string $path, array $data = [], array $headers = []): TestResponse
    {
        return $this->makeRequest('PUT', $path, [], $data, $headers);
    }
    
    /**
     * Make a PATCH request
     */
    protected function patch(string $path, array $data = [], array $headers = []): TestResponse
    {
        return $this->makeRequest('PATCH', $path, [], $data, $headers);
    }
    
    /**
     * Make a DELETE request
     */
    protected function delete(string $path, array $data = [], array $headers = []): TestResponse
    {
        return $this->makeRequest('DELETE', $path, [], $data, $headers);
    }
    
    /**
     * Make a JSON request
     */
    protected function json(string $method, string $path, array $data = [], array $headers = []): TestResponse
    {
        $headers['Content-Type'] = 'application/json';
        return $this->makeRequest($method, $path, [], $data, $headers);
    }
    
    /**
     * Make a request to the application
     */
    protected function makeRequest(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        array $headers = []
    ): TestResponse {
        // Ensure Content-Type is set
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }
        
        // Create request
        $request = new Request(
            $method,
            $path,
            $headers,
            $query,
            $body,
            $body === [] ? null : json_encode($body),
            [],
            [],
            [],
            'test-' . uniqid()
        );
        
        // Find and execute the route
        $router = App::router();
        
        // Load routes if not already loaded
        $this->loadRoutes();
        
        // Dispatch the request
        $matchResult = $router->match($method, $path);
        
        if (!$matchResult) {
            // Return 404 response
            return new TestResponse(JsonResponse::notFound('Route not found'));
        }
        
        [$route, $pathParams] = $matchResult;
        
        // Set path parameters
        $request->pathParams = $pathParams;
        
        // Instantiate controller
        $controllerClass = $route->controllerClass();
        $controller = new $controllerClass();
        
        if (!$controller instanceof Controller) {
            throw new RuntimeException("Controller must extend BaseApi\\Controllers\\Controller");
        }
        
        // Inject request data into controller properties
        $this->injectRequestData($controller, $request, $pathParams);
        
        // Get the handler method
        $handlerMethod = strtolower($method);
        
        if (!method_exists($controller, $handlerMethod)) {
            return new TestResponse(JsonResponse::error('Method not allowed', 405));
        }
        
        // Execute the handler
        try {
            $response = $controller->$handlerMethod();
            
            if (!$response instanceof JsonResponse) {
                throw new RuntimeException("Controller method must return JsonResponse");
            }
            
            return new TestResponse($response);
        } catch (Exception $exception) {
            return new TestResponse(JsonResponse::error('Internal server error: ' . $exception->getMessage(), 500));
        }
    }
    
    /**
     * Inject request data into controller properties
     */
    private function injectRequestData(Controller $controller, Request $request, array $pathParams): void
    {
        $reflection = new ReflectionClass($controller);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            
            // Skip if property is not writable
            if (!$property->isPublic()) {
                continue;
            }
            
            // Try path params first
            if (isset($pathParams[$propertyName])) {
                $this->setPropertyValue($controller, $property, $pathParams[$propertyName]);
                continue;
            }
            
            // Try query params
            if (isset($request->query[$propertyName])) {
                $this->setPropertyValue($controller, $property, $request->query[$propertyName]);
                continue;
            }
            
            // Try body params
            if (isset($request->body[$propertyName])) {
                $this->setPropertyValue($controller, $property, $request->body[$propertyName]);
                continue;
            }
        }
    }
    
    /**
     * Set property value with type coercion
     */
    private function setPropertyValue(Controller $controller, ReflectionProperty $property, mixed $value): void
    {
        $type = $property->getType();
        
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            
            // Type coercion
            $value = match ($typeName) {
                'int' => (int) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'array' => is_array($value) ? $value : [$value],
                default => $value
            };
        }
        
        $property->setValue($controller, $value);
    }
    
    /**
     * Load application routes
     */
    protected function loadRoutes(): void
    {
        static $routesLoaded = false;
        
        if ($routesLoaded) {
            return;
        }
        
        $routesFile = App::basePath('routes/api.php');
        
        if (file_exists($routesFile)) {
            require $routesFile;
            $routesLoaded = true;
        }
    }
    
    /**
     * Assert that two arrays are equal, ignoring order
     */
    protected function assertArrayEquals(array $expected, array $actual, string $message = ''): void
    {
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual, $message);
    }
    
    /**
     * Create a mock user for testing
     */
    protected function actingAs(array $user): self
    {
        // This can be extended to set up authentication for tests
        return $this;
    }
}

