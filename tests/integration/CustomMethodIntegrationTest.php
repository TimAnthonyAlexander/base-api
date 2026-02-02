<?php

namespace BaseApi\Tests\Integration;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Router;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\Binding\ControllerBinder;
use BaseApi\Http\ControllerInvoker;
use BaseApi\Controllers\Controller;
use BaseApi\Container\Container;

class CustomMethodIntegrationTest extends TestCase
{
    private Router $router;

    private Container $container;

    #[Override]
    protected function setUp(): void
    {
        $this->router = new Router();
        $this->container = new Container();

        // Register required services
        $this->container->bind(ControllerBinder::class, fn(): ControllerBinder => new ControllerBinder());
        $this->container->bind(ControllerInvoker::class, fn(): ControllerInvoker => new ControllerInvoker());
    }

    public function testCustomMethodRouteWithFullPipeline(): void
    {
        // Register route with custom method
        $this->router->get('/api/users/{id}', [[IntegrationTestController::class, 'getUserById']]);

        // Match the route
        $match = $this->router->match('GET', '/api/users/123');
        $this->assertNotNull($match);

        [$route, $params] = $match;

        // Verify route details
        $this->assertEquals(IntegrationTestController::class, $route->controllerClass());
        $this->assertEquals('getUserById', $route->controllerMethod());
        $this->assertEquals(['id' => '123'], $params);

        // Simulate full request handling
        $controller = $this->container->make(IntegrationTestController::class);
        $request = $this->createRequest('GET', '/api/users/123');

        // Bind request data
        $binder = $this->container->make(ControllerBinder::class);
        $binder->bind($controller, $request, $params);

        // Invoke the custom method
        $invoker = $this->container->make(ControllerInvoker::class);
        $response = $invoker->invoke($controller, $request, 'getUserById');

        // Assert response
        $this->assertEquals(200, $response->status);
        $body = json_decode($response->body, true);
        $this->assertEquals('123', $body['id']);
        $this->assertEquals('User 123', $body['name']);
    }

    public function testMultipleCustomMethodsOnSameController(): void
    {
        // Register multiple routes with different custom methods
        $this->router->get('/api/users', [[IntegrationTestController::class, 'listUsers']]);
        $this->router->post('/api/users', [[IntegrationTestController::class, 'createUser']]);
        $this->router->get('/api/users/{id}', [[IntegrationTestController::class, 'getUserById']]);
        $this->router->put('/api/users/{id}', [[IntegrationTestController::class, 'updateUser']]);
        $this->router->delete('/api/users/{id}', [[IntegrationTestController::class, 'deleteUser']]);

        // Test list users
        $this->assertRouteMethod('GET', '/api/users', 'listUsers');

        // Test create user
        $this->assertRouteMethod('POST', '/api/users', 'createUser');

        // Test get user by id
        $this->assertRouteMethod('GET', '/api/users/456', 'getUserById');

        // Test update user
        $this->assertRouteMethod('PUT', '/api/users/789', 'updateUser');

        // Test delete user
        $this->assertRouteMethod('DELETE', '/api/users/999', 'deleteUser');
    }

    public function testCustomMethodWithQueryAndBodyParams(): void
    {
        // Register route
        $this->router->post('/api/users', [[IntegrationTestController::class, 'createUser']]);

        // Match the route
        $match = $this->router->match('POST', '/api/users');
        $this->assertNotNull($match);

        [$route, $params] = $match;

        // Create request with body data
        $request = new Request(
            'POST',
            '/api/users',
            [],
            ['filter' => 'active'],  // query params
            ['name' => 'John Doe', 'email' => 'john@example.com'],  // body params
            null,
            [],
            [],
            [],
            'test-request-id'
        );

        // Bind and invoke
        $controller = $this->container->make(IntegrationTestController::class);
        $binder = $this->container->make(ControllerBinder::class);
        $binder->bind($controller, $request, $params);

        $invoker = $this->container->make(ControllerInvoker::class);
        $response = $invoker->invoke($controller, $request, 'createUser');

        // Assert response
        $this->assertEquals(201, $response->status);
        $body = json_decode($response->body, true);
        $this->assertEquals('John Doe', $body['name']);
        $this->assertEquals('john@example.com', $body['email']);
    }

    public function testStandardMethodStillWorks(): void
    {
        // Register route without custom method (standard HTTP method-based)
        $this->router->get('/api/standard', [StandardMethodTestController::class]);

        // Match the route
        $match = $this->router->match('GET', '/api/standard');
        $this->assertNotNull($match);

        [$route, $params] = $match;

        // Should not have a custom method
        $this->assertNull($route->controllerMethod());

        // Invoke should use HTTP method-based routing
        $controller = $this->container->make(StandardMethodTestController::class);
        $request = $this->createRequest('GET', '/api/standard');

        $binder = $this->container->make(ControllerBinder::class);
        $binder->bind($controller, $request, $params);

        $invoker = $this->container->make(ControllerInvoker::class);
        $response = $invoker->invoke($controller, $request);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('Standard GET method', $response->body);
    }

    private function assertRouteMethod(string $httpMethod, string $path, string $expectedMethod): void
    {
        $match = $this->router->match($httpMethod, $path);
        $this->assertNotNull($match);

        [$route, $params] = $match;
        $this->assertEquals($expectedMethod, $route->controllerMethod());
    }

    private function createRequest(string $method, string $path): Request
    {
        return new Request(
            $method,
            $path,
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

// Test controllers
class IntegrationTestController extends Controller
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $email = null;

    public function listUsers(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'users' => [
                ['id' => '1', 'name' => 'User 1'],
                ['id' => '2', 'name' => 'User 2'],
            ]
        ]));
    }

    public function getUserById(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => $this->id,
            'name' => 'User ' . $this->id
        ]));
    }

    public function createUser(): Response
    {
        return new Response(201, ['Content-Type' => 'application/json'], json_encode([
            'id' => uniqid(),
            'name' => $this->name,
            'email' => $this->email,
            'message' => 'User created'
        ]));
    }

    public function updateUser(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => $this->id,
            'message' => 'User updated'
        ]));
    }

    public function deleteUser(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => $this->id,
            'message' => 'User deleted'
        ]));
    }
}

class StandardMethodTestController extends Controller
{
    public function get(): Response
    {
        return new Response(200, [], 'Standard GET method');
    }
}
