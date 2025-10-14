<?php

namespace BaseApi\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Router;
use BaseApi\Route;

class RouterTest extends TestCase
{
    private Router $router;
    
    #[Override]
    protected function setUp(): void
    {
        $this->router = new Router();
    }
    
    public function testGetRouteRegistration(): void
    {
        $pipeline = ['SomeMiddleware', 'SomeController'];
        
        $this->router->get('/users', $pipeline);
        
        $result = $this->router->match('GET', '/users');
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        
        [$route, $params] = $result;
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('GET', $route->method());
        $this->assertEquals('/users', $route->path());
        $this->assertEquals($pipeline, [$route->middlewares()[0], $route->controllerClass()]);
        $this->assertEquals([], $params);
    }
    
    public function testPostRouteRegistration(): void
    {
        $pipeline = ['ValidationMiddleware', 'UserController'];
        
        $this->router->post('/users', $pipeline);
        
        $result = $this->router->match('POST', '/users');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals('POST', $route->method());
        $this->assertEquals('/users', $route->path());
        $this->assertEquals('UserController', $route->controllerClass());
    }
    
    public function testPutRouteRegistration(): void
    {
        $pipeline = ['AuthMiddleware', 'UserController'];
        
        $this->router->put('/users/{id}', $pipeline);
        
        $result = $this->router->match('PUT', '/users/123');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals('PUT', $route->method());
        $this->assertEquals('/users/{id}', $route->path());
        $this->assertEquals(['id' => '123'], $params);
    }
    
    public function testPatchRouteRegistration(): void
    {
        $pipeline = ['AuthMiddleware', 'UserController'];
        
        $this->router->patch('/users/{id}', $pipeline);
        
        $result = $this->router->match('PATCH', '/users/456');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals('PATCH', $route->method());
        $this->assertEquals(['id' => '456'], $params);
    }
    
    public function testDeleteRouteRegistration(): void
    {
        $pipeline = ['AuthMiddleware', 'UserController'];
        
        $this->router->delete('/users/{id}', $pipeline);
        
        $result = $this->router->match('DELETE', '/users/789');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals('DELETE', $route->method());
        $this->assertEquals(['id' => '789'], $params);
    }
    
    public function testOptionsRouteRegistration(): void
    {
        $pipeline = ['CorsMiddleware', 'OptionsController'];
        
        $this->router->options('/api/{resource}', $pipeline);
        
        $result = $this->router->match('OPTIONS', '/api/users');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals('OPTIONS', $route->method());
        $this->assertEquals(['resource' => 'users'], $params);
    }
    
    public function testHeadRouteRegistration(): void
    {
        $pipeline = ['CacheMiddleware', 'HeadController'];
        
        $this->router->head('/status', $pipeline);
        
        $result = $this->router->match('HEAD', '/status');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals('HEAD', $route->method());
        $this->assertEquals('/status', $route->path());
    }
    
    public function testRouteWithMultipleParameters(): void
    {
        $pipeline = ['AuthMiddleware', 'PostController'];
        
        $this->router->get('/users/{userId}/posts/{postId}', $pipeline);
        
        $result = $this->router->match('GET', '/users/123/posts/456');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals('GET', $route->method());
        $this->assertEquals('/users/{userId}/posts/{postId}', $route->path());
        $this->assertEquals(['userId' => '123', 'postId' => '456'], $params);
    }
    
    public function testRouteWithComplexParameterNames(): void
    {
        $pipeline = ['Controller'];
        
        $this->router->get('/api/v1/{resource_type}/items/{item_id}', $pipeline);
        
        $result = $this->router->match('GET', '/api/v1/user_profiles/items/abc-123');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals(['resource_type' => 'user_profiles', 'item_id' => 'abc-123'], $params);
    }
    
    public function testRouteMatchingIsCaseSensitive(): void
    {
        $pipeline = ['Controller'];
        
        $this->router->get('/Users', $pipeline);
        
        // Exact match should work
        $result = $this->router->match('GET', '/Users');
        $this->assertNotNull($result);
        
        // Case mismatch should not work
        $result = $this->router->match('GET', '/users');
        $this->assertNull($result);
        
        $result = $this->router->match('GET', '/USERS');
        $this->assertNull($result);
    }
    
    public function testMethodMatchingIsCaseSensitive(): void
    {
        $pipeline = ['Controller'];
        
        $this->router->get('/users', $pipeline);
        
        // Correct method should work
        $result = $this->router->match('GET', '/users');
        $this->assertNotNull($result);
        
        // Wrong method should not work
        $result = $this->router->match('POST', '/users');
        $this->assertNull($result);
        
        // Case mismatch should not work
        $result = $this->router->match('get', '/users');
        $this->assertNull($result);
    }
    
    public function testNoMatchReturnsNull(): void
    {
        $pipeline = ['Controller'];
        
        $this->router->get('/users', $pipeline);
        
        $result = $this->router->match('GET', '/posts');
        $this->assertNull($result);
        
        $result = $this->router->match('POST', '/users');
        $this->assertNull($result);
    }
    
    public function testRouteWithMiddlewareChain(): void
    {
        $pipeline = ['AuthMiddleware', 'ValidationMiddleware', 'LogMiddleware', 'UserController'];
        
        $this->router->post('/users', $pipeline);
        
        $result = $this->router->match('POST', '/users');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $middlewares = $route->middlewares();
        
        $this->assertEquals(['AuthMiddleware', 'ValidationMiddleware', 'LogMiddleware'], $middlewares);
        $this->assertEquals('UserController', $route->controllerClass());
    }
    
    public function testRouteWithOnlyController(): void
    {
        $pipeline = ['UserController'];
        
        $this->router->get('/simple', $pipeline);
        
        $result = $this->router->match('GET', '/simple');
        $this->assertNotNull($result);
        
        [$route, $params] = $result;
        $this->assertEquals([], $route->middlewares());
        $this->assertEquals('UserController', $route->controllerClass());
    }
    
    public function testMultipleSimilarRoutes(): void
    {
        $this->router->get('/users', ['UserListController']);
        $this->router->get('/users/{id}', ['UserShowController']);
        $this->router->post('/users', ['UserCreateController']);
        
        // Test first route
        $result = $this->router->match('GET', '/users');
        $this->assertNotNull($result);
        [$route, $params] = $result;
        $this->assertEquals('UserListController', $route->controllerClass());
        $this->assertEquals([], $params);
        
        // Test parameterized route
        $result = $this->router->match('GET', '/users/123');
        $this->assertNotNull($result);
        [$route, $params] = $result;
        $this->assertEquals('UserShowController', $route->controllerClass());
        $this->assertEquals(['id' => '123'], $params);
        
        // Test different method on same path
        $result = $this->router->match('POST', '/users');
        $this->assertNotNull($result);
        [$route, $params] = $result;
        $this->assertEquals('UserCreateController', $route->controllerClass());
    }
    
    public function testAllowedMethodsForPath(): void
    {
        $this->router->get('/users', ['GetController']);
        $this->router->post('/users', ['PostController']);
        $this->router->put('/users', ['PutController']);
        $this->router->delete('/users', ['DeleteController']);

        $allowedMethods = $this->router->allowedMethodsForPath('/users');

        $this->assertCount(5, $allowedMethods); // GET, POST, PUT, DELETE, HEAD (auto-added)
        $this->assertContains('GET', $allowedMethods);
        $this->assertContains('POST', $allowedMethods);
        $this->assertContains('PUT', $allowedMethods);
        $this->assertContains('DELETE', $allowedMethods);
        $this->assertContains('HEAD', $allowedMethods); // HEAD auto-added for GET routes
    }
    
    public function testAllowedMethodsForParameterizedPath(): void
    {
        $this->router->get('/users/{id}', ['GetController']);
        $this->router->patch('/users/{id}', ['PatchController']);
        $this->router->delete('/users/{id}', ['DeleteController']);

        $allowedMethods = $this->router->allowedMethodsForPath('/users/123');

        $this->assertCount(4, $allowedMethods); // GET, PATCH, DELETE, HEAD (auto-added)
        $this->assertContains('GET', $allowedMethods);
        $this->assertContains('PATCH', $allowedMethods);
        $this->assertContains('DELETE', $allowedMethods);
        $this->assertContains('HEAD', $allowedMethods); // HEAD auto-added for GET routes
    }
    
    public function testAllowedMethodsForNonExistentPath(): void
    {
        $this->router->get('/users', ['Controller']);
        
        $allowedMethods = $this->router->allowedMethodsForPath('/posts');
        
        $this->assertEquals([], $allowedMethods);
    }
    
    public function testAllowedMethodsReturnsUniqueValues(): void
    {
        // Register same method multiple times (shouldn't happen in practice, but let's be defensive)
        $this->router->get('/test', ['Controller1']);
        $this->router->get('/test', ['Controller2']);
        
        $allowedMethods = $this->router->allowedMethodsForPath('/test');
        
        // Should return GET and HEAD (auto-added for GET routes)
        $this->assertCount(2, $allowedMethods);
        $this->assertContains('GET', $allowedMethods);
        $this->assertContains('HEAD', $allowedMethods);
    }
    
    public function testEmptyRouterMatchesNothing(): void
    {
        $result = $this->router->match('GET', '/anything');
        $this->assertNull($result);
        
        $allowedMethods = $this->router->allowedMethodsForPath('/anything');
        $this->assertEquals([], $allowedMethods);
    }
    
    public function testRouteParametersWithSpecialCharacters(): void
    {
        $this->router->get('/files/{filename}', ['FileController']);
        
        // Test with various filename patterns
        $testCases = [
            'simple.txt' => 'simple.txt',
            'file-with-dashes.pdf' => 'file-with-dashes.pdf',
            'file_with_underscores.doc' => 'file_with_underscores.doc',
            'file%20with%20spaces.txt' => 'file%20with%20spaces.txt',
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $this->router->match('GET', '/files/' . $input);
            $this->assertNotNull($result, 'Should match filename: ' . $input);
            
            [$route, $params] = $result;
            $this->assertEquals($expected, $params['filename']);
        }
    }
    
    public function testRouteOrderMatters(): void
    {
        // Register more specific route first
        $this->router->get('/users/profile', ['ProfileController']);
        // Register more general route second  
        $this->router->get('/users/{id}', ['UserController']);
        
        // More specific route should match first
        $result = $this->router->match('GET', '/users/profile');
        $this->assertNotNull($result);
        [$route, $params] = $result;
        $this->assertEquals('ProfileController', $route->controllerClass());
        $this->assertEquals([], $params); // No parameters for exact match
        
        // General route should still work for other cases
        $result = $this->router->match('GET', '/users/123');
        $this->assertNotNull($result);
        [$route, $params] = $result;
        $this->assertEquals('UserController', $route->controllerClass());
        $this->assertEquals(['id' => '123'], $params);
    }
}
