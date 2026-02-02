<?php

namespace BaseApi\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Router;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\ControllerInvoker;
use BaseApi\Controllers\Controller;

class CustomControllerMethodTest extends TestCase
{
    private Router $router;

    private ControllerInvoker $invoker;

    #[Override]
    protected function setUp(): void
    {
        $this->router = new Router();
        $this->invoker = new ControllerInvoker();
    }

    public function testRouteWithCustomMethod(): void
    {
        // Register route with custom method syntax (wrapped in array)
        $this->router->get('/test', [[TestCustomController::class, 'customMethod']]);

        // Match the route
        $match = $this->router->match('GET', '/test');

        $this->assertNotNull($match);
        [$route, $params] = $match;

        // Verify the route has the correct controller class
        $this->assertEquals(TestCustomController::class, $route->controllerClass());

        // Verify the route has the correct custom method
        $this->assertEquals('customMethod', $route->controllerMethod());
    }

    public function testRouteWithStandardMethod(): void
    {
        // Register route with standard syntax (wrapped in array)
        $this->router->get('/standard', [TestStandardController::class]);

        // Match the route
        $match = $this->router->match('GET', '/standard');

        $this->assertNotNull($match);
        [$route, $params] = $match;

        // Verify the route has the correct controller class
        $this->assertEquals(TestStandardController::class, $route->controllerClass());

        // Verify there's no custom method
        $this->assertNull($route->controllerMethod());
    }

    public function testInvokerWithCustomMethod(): void
    {
        $controller = new TestCustomController();
        $request = $this->createRequest();

        // Invoke with custom method
        $response = $this->invoker->invoke($controller, $request, 'customMethod');

        $this->assertEquals(200, $response->status);
        $this->assertEquals('Custom method response', $response->body);
    }

    public function testInvokerWithStandardMethod(): void
    {
        $controller = new TestStandardController();
        $request = $this->createRequest();

        // Invoke without custom method (should use HTTP method-based routing)
        $response = $this->invoker->invoke($controller, $request);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('GET method response', $response->body);
    }

    public function testInvokerWithNonExistentCustomMethod(): void
    {
        $controller = new TestCustomController();
        $request = $this->createRequest();

        // Invoke with non-existent custom method
        $response = $this->invoker->invoke($controller, $request, 'nonExistentMethod');

        $this->assertEquals(500, $response->status);
    }

    public function testRouteWithMiddlewareAndCustomMethod(): void
    {
        // Register route with middleware and custom method
        $this->router->get('/middleware-test', [
            'SomeMiddleware',
            'AnotherMiddleware',
            [TestCustomController::class, 'customMethod']
        ]);

        // Match the route
        $match = $this->router->match('GET', '/middleware-test');

        $this->assertNotNull($match);
        [$route, $params] = $match;

        // Verify middlewares
        $this->assertEquals(['SomeMiddleware', 'AnotherMiddleware'], $route->middlewares());

        // Verify controller and method
        $this->assertEquals(TestCustomController::class, $route->controllerClass());
        $this->assertEquals('customMethod', $route->controllerMethod());
    }

    public function testRouteWithDynamicParamsAndCustomMethod(): void
    {
        // Register route with dynamic params and custom method
        $this->router->get('/users/{id}', [[TestCustomController::class, 'getUserById']]);

        // Match the route
        $match = $this->router->match('GET', '/users/123');

        $this->assertNotNull($match);
        [$route, $params] = $match;

        // Verify params were extracted
        $this->assertEquals(['id' => '123'], $params);

        // Verify custom method
        $this->assertEquals('getUserById', $route->controllerMethod());
    }

    public function testMultipleRoutesWithDifferentMethods(): void
    {
        // Register multiple routes to same controller with different custom methods
        $this->router->get('/items', [[TestCustomController::class, 'listItems']]);
        $this->router->post('/items', [[TestCustomController::class, 'createItem']]);
        $this->router->get('/items/{id}', [[TestCustomController::class, 'getItem']]);
        $this->router->delete('/items/{id}', [[TestCustomController::class, 'deleteItem']]);

        // Test GET /items
        $match = $this->router->match('GET', '/items');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertEquals('listItems', $route->controllerMethod());

        // Test POST /items
        $match = $this->router->match('POST', '/items');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertEquals('createItem', $route->controllerMethod());

        // Test GET /items/123
        $match = $this->router->match('GET', '/items/123');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertEquals('getItem', $route->controllerMethod());
        $this->assertEquals(['id' => '123'], $params);

        // Test DELETE /items/456
        $match = $this->router->match('DELETE', '/items/456');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertEquals('deleteItem', $route->controllerMethod());
        $this->assertEquals(['id' => '456'], $params);
    }

    private function createRequest(): Request
    {
        return new Request(
            'GET',
            '/test',
            [],
            [],
            [],
            null,
            [],
            [],
            [],
            'test-request-id'
        );
    }
}

// Test controllers for custom method routing
class TestCustomController extends Controller
{
    public function customMethod(): Response
    {
        return new Response(200, [], 'Custom method response');
    }

    public function getUserById(): Response
    {
        return new Response(200, [], 'User by ID');
    }

    public function listItems(): Response
    {
        return new Response(200, [], 'List items');
    }

    public function createItem(): Response
    {
        return new Response(200, [], 'Create item');
    }

    public function getItem(): Response
    {
        return new Response(200, [], 'Get item');
    }

    public function deleteItem(): Response
    {
        return new Response(200, [], 'Delete item');
    }
}

class TestStandardController extends Controller
{
    public function get(): Response
    {
        return new Response(200, [], 'GET method response');
    }
}
