<?php

namespace BaseApi\Http;

use BaseApi\App;
use BaseApi\Route;
use BaseApi\Router;
use BaseApi\Http\Binding\ControllerBinder;
use BaseApi\Http\Validation\ValidationException;
use BaseApi\Http\Middleware\OptionedMiddleware;
use BaseApi\Container\ContainerInterface;

class Kernel
{
    private array $globalMiddleware = [];

    private ?ControllerBinder $binder = null;

    private ?ControllerInvoker $invoker = null;

    public function __construct(private readonly Router $router, private ?ContainerInterface $container = null)
    {
        // Defer binder and invoker creation until container is available
        if ($this->container instanceof ContainerInterface) {
            $this->binder = $this->container->make(ControllerBinder::class);
            $this->invoker = $this->container->make(ControllerInvoker::class);
        }
    }

    public function addGlobal(string $middlewareClass): void
    {
        $this->globalMiddleware[] = $middlewareClass;
    }

    private function getBinder(): ControllerBinder
    {
        if (!$this->binder instanceof ControllerBinder) {
            if (!$this->container instanceof ContainerInterface) {
                $this->container = App::container();
            }

            $this->binder = $this->container->make(ControllerBinder::class);
        }

        return $this->binder;
    }

    private function getInvoker(): ControllerInvoker
    {
        if (!$this->invoker instanceof ControllerInvoker) {
            if (!$this->container instanceof ContainerInterface) {
                $this->container = App::container();
            }

            $this->invoker = $this->container->make(ControllerInvoker::class);
        }

        return $this->invoker;
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
        $path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
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

    /**
     * @return callable(Request): Response
     */
    private function buildPipeline(Request $request): callable
    {
        // Try to match route
        $routeMatch = $this->router->match($request->method, $request->path);

        if ($routeMatch === null) {
            // Check if path exists for other methods (for OPTIONS)
            $allowedMethods = $this->router->allowedMethodsForPath($request->path);

            if ($request->method === 'OPTIONS' && $allowedMethods !== []) {
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
            $routeMiddlewares[] = is_string($key) && is_array($value) ? [$key => $value] : $value;
        }

        $middlewareStack = array_merge(
            $this->globalMiddleware,
            $routeMiddlewares,
            [$route->controllerClass()]
        );

        return $this->createPipeline($middlewareStack, $pathParams, $route);
    }

    /**
     * @return callable(Request): Response
     */
    private function createPipeline(array $middlewareStack, array $pathParams, ?Route $route): callable
    {
        $pipeline = fn(Request $request): Response =>
            // This should never be reached
            new Response(500, [], 'Pipeline exhausted');

        // Build pipeline in reverse order
        for ($i = count($middlewareStack) - 1; $i >= 0; $i--) {
            $middlewareClass = $middlewareStack[$i];
            $next = $pipeline;
            $isController = ($i === count($middlewareStack) - 1);

            $pipeline = function(Request $request) use ($middlewareClass, $next, $pathParams, $isController, $route) {
                // Add route info to request for middleware use
                if ($route instanceof Route) {
                    $request->routePattern = $route->path();
                    $request->routeMethod = $route->method();
                }

                // Check if this is the controller (last in pipeline)
                if ($isController) {
                    return $this->invokeController($middlewareClass, $request, $pathParams);
                }

                // Handle optioned middleware (ClassName::class => [options])
                if (is_string($middlewareClass)) {
                    // Regular middleware - use container
                    if (!$this->container instanceof ContainerInterface) {
                        $this->container = App::container();
                    }

                    $middleware = $this->container->make($middlewareClass);
                } else {
                    // This is an associative array with class => options
                    $className = key($middlewareClass);
                    $options = current($middlewareClass);
                    if (!$this->container instanceof ContainerInterface) {
                        $this->container = App::container();
                    }

                    $middleware = $this->container->make($className);

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
            // Instantiate controller using container
            if (!$this->container instanceof ContainerInterface) {
                $this->container = App::container();
            }

            $controller = $this->container->make($controllerClass);

            // Bind request data to controller properties
            $this->getBinder()->bind($controller, $request, $pathParams);

            // Invoke the controller method
            return $this->getInvoker()->invoke($controller, $request);
        } catch (ValidationException $validationException) {
            // Return 400 with validation errors
            return JsonResponse::badRequest('Validation failed.', $validationException->errors());
        }
    }

    private function sendResponse(Response $response): void
    {
        // Set status code
        http_response_code($response->status);

        // Explicitly remove Connection header if we're setting our own
        // This ensures PHP built-in server's Connection: close is overridden
        foreach (array_keys($response->headers) as $headerName) {
            if (strcasecmp($headerName, 'Connection') === 0) {
                header_remove('Connection');
                break;
            }
        }

        // Set headers
        foreach ($response->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), true);
        }

        // Handle streamed responses
        if ($response instanceof StreamedResponse) {
            $response->sendContent();
            return;
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
