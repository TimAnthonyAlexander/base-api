<?php

namespace BaseApi\Http;

use BaseApi\Router;
use BaseApi\Http\Binding\ControllerBinder;
use BaseApi\Http\Validation\ValidationException;
use BaseApi\Http\Middleware\OptionedMiddleware;

class Kernel
{
    private Router $router;
    private array $globalMiddleware = [];
    private ControllerBinder $binder;
    private ControllerInvoker $invoker;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->binder = new ControllerBinder();
        $this->invoker = new ControllerInvoker();
    }

    public function addGlobal(string $middlewareClass): void
    {
        $this->globalMiddleware[] = $middlewareClass;
    }

    public function handle(): void
    {
        // Build request from globals
        $request = $this->buildRequest();

        // Build middleware pipeline
        $pipeline = $this->buildPipeline($request);

        // Execute pipeline
        $response = $pipeline($request);

        // Send response
        $this->sendResponse($response);
    }

    private function buildRequest(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $query = $_GET;
        $cookies = $_COOKIE;
        
        // Get headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }
        
        // Handle Content-Type and Content-Length specially
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        // Get raw body
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false) {
            $rawBody = null;
        }

        // Initialize empty session array (will be populated by SessionStartMiddleware)
        $session = [];

        return new Request(
            $method,
            $path,
            $headers,
            $query,
            [], // body will be populated by JsonBodyParserMiddleware
            $rawBody,
            [], // files will be populated by JsonBodyParserMiddleware
            $cookies,
            $session,
            '' // requestId will be populated by RequestIdMiddleware
        );
    }

    private function buildPipeline(Request $request): callable
    {
        // Try to match route
        $routeMatch = $this->router->match($request->method, $request->path);
        
        if ($routeMatch === null) {
            // Check if path exists for other methods (for OPTIONS)
            $allowedMethods = $this->router->allowedMethodsForPath($request->path);
            
            if ($request->method === 'OPTIONS' && !empty($allowedMethods)) {
                // Let OPTIONS go through global middleware (including CORS)
                // Add allowed methods to request for CORS middleware to use
                $request->allowedMethods = array_merge($allowedMethods, ['OPTIONS']);
                
                $middlewareStack = array_merge(
                    $this->globalMiddleware,
                    ['OptionsHandler']
                );
                return $this->createPipeline($middlewareStack, [], null);
            }
            
            // 404 Not Found - also goes through global middleware for CORS
            $middlewareStack = array_merge(
                $this->globalMiddleware,
                ['NotFoundHandler']
            );
            return $this->createPipeline($middlewareStack, [], null);
        }

        [$route, $pathParams] = $routeMatch;

        // Build complete middleware stack: global + route-specific + controller
        // Convert route middlewares to the format expected by createPipeline
        $routeMiddlewares = [];
        foreach ($route->middlewares() as $key => $value) {
            if (is_string($key) && is_array($value)) {
                // Optioned middleware: class => options
                $routeMiddlewares[] = [$key => $value];
            } else {
                // Regular middleware
                $routeMiddlewares[] = $value;
            }
        }
        
        $middlewareStack = array_merge(
            $this->globalMiddleware,
            $routeMiddlewares,
            [$route->controllerClass()]
        );

        return $this->createPipeline($middlewareStack, $pathParams, $route);
    }

    private function createPipeline(array $middlewareStack, array $pathParams, ?\BaseApi\Route $route): callable
    {
        $pipeline = function(Request $request) {
            // This should never be reached
            return new Response(500, [], 'Pipeline exhausted');
        };

        // Build pipeline in reverse order
        for ($i = count($middlewareStack) - 1; $i >= 0; $i--) {
            $middlewareClass = $middlewareStack[$i];
            $next = $pipeline;
            $isController = ($i === count($middlewareStack) - 1);

            $pipeline = function(Request $request) use ($middlewareClass, $next, $pathParams, $isController, $route) {
                // Add route info to request for middleware use
                if ($route) {
                    $request->routePattern = $route->path();
                    $request->routeMethod = $route->method();
                }
                
                // Check if this is the controller (last in pipeline)
                if ($isController) {
                    return $this->invokeController($middlewareClass, $request, $pathParams);
                }

                // Handle optioned middleware (ClassName::class => [options])
                if (is_string($middlewareClass)) {
                    // Regular middleware
                    $middleware = new $middlewareClass();
                } else {
                    // This is an associative array with class => options
                    $className = key($middlewareClass);
                    $options = current($middlewareClass);
                    $middleware = new $className();
                    
                    if ($middleware instanceof OptionedMiddleware) {
                        $middleware->setOptions($options);
                    }
                }
                
                return $middleware->handle($request, $next);
            };
        }

        return $pipeline;
    }

    private function invokeController(string $controllerClass, Request $request, array $pathParams): Response
    {
        // Handle special internal handlers
        if ($controllerClass === 'OptionsHandler') {
            return new Response(204, [
                'Allow' => implode(', ', $request->allowedMethods)
            ]);
        }
        
        if ($controllerClass === 'NotFoundHandler') {
            return JsonResponse::notFound();
        }

        try {
            // Instantiate controller
            $controller = new $controllerClass();

            // Bind request data to controller properties
            $this->binder->bind($controller, $request, $pathParams);

            // Invoke the controller method
            return $this->invoker->invoke($controller, $request);
        } catch (ValidationException $e) {
            // Return 400 with validation errors
            return JsonResponse::badRequest('Validation failed.', $e->errors());
        }
    }

    private function sendResponse(Response $response): void
    {
        // Set status code
        http_response_code($response->status);

        // Set headers
        foreach ($response->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Send body
        if (is_resource($response->body)) {
            fpassthru($response->body);
            fclose($response->body);
        } else {
            echo $response->body;
        }
    }
}
