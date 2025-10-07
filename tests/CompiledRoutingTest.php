<?php

namespace BaseApi\Tests;

use Override;
use ReflectionClass;
use BaseApi\Router;
use BaseApi\Route;
use BaseApi\Routing\CompiledRoute;
use BaseApi\Routing\RouteCompiler;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for compiled routing system.
 * Ensures performance optimizations work correctly while maintaining backwards compatibility.
 */
class CompiledRoutingTest extends TestCase
{
    private Router $router;

    private string $tempCachePath;

    #[Override]
    protected function setUp(): void
    {
        $this->router = new Router();
        $this->tempCachePath = sys_get_temp_dir() . '/test_routes_' . uniqid() . '.php';
    }

    #[Override]
    protected function tearDown(): void
    {
        if (file_exists($this->tempCachePath)) {
            unlink($this->tempCachePath);
        }
    }

    public function testStaticRouteCompilation(): void
    {
        // Register static routes
        $this->router->get('/users', ['UsersController']);
        $this->router->post('/users', ['CreateUserController']);
        $this->router->get('/health', ['HealthController']);

        // Compile routes
        $this->assertTrue($this->router->compile($this->tempCachePath));
        $this->assertFileExists($this->tempCachePath);

        // Load compiled routes
        $compiled = require $this->tempCachePath;

        // Verify structure
        $this->assertIsArray($compiled);
        $this->assertArrayHasKey('static', $compiled);
        $this->assertArrayHasKey('dynamic', $compiled);
        $this->assertArrayHasKey('methods', $compiled);

        // Verify static routes
        $this->assertArrayHasKey('GET', $compiled['static']);
        $this->assertArrayHasKey('/users', $compiled['static']['GET']);
        $this->assertArrayHasKey('/health', $compiled['static']['GET']);

        $this->assertArrayHasKey('POST', $compiled['static']);
        $this->assertArrayHasKey('/users', $compiled['static']['POST']);
    }

    public function testDynamicRouteCompilation(): void
    {
        // Register dynamic routes
        $this->router->get('/users/{id}', ['ShowUserController']);
        $this->router->get('/posts/{id}/comments/{commentId}', ['ShowCommentController']);

        // Compile routes
        $this->assertTrue($this->router->compile($this->tempCachePath));

        // Load compiled routes from cache (arrays, not objects)
        $compiled = require $this->tempCachePath;

        // Verify dynamic routes
        $this->assertArrayHasKey('GET', $compiled['dynamic']);
        $this->assertNotEmpty($compiled['dynamic']['GET']);

        // Cache exports routes as arrays for Opcache efficiency
        $route = $compiled['dynamic']['GET'][0];
        $this->assertIsArray($route);
        $this->assertArrayHasKey('isStatic', $route);
        $this->assertFalse($route['isStatic']);
        $this->assertArrayHasKey('paramNames', $route);
        $this->assertNotEmpty($route['paramNames']);
    }

    public function testCompiledRouteMatching(): void
    {
        // Register routes
        $this->router->get('/users', ['UsersController']);
        $this->router->get('/users/{id}', ['ShowUserController']);
        $this->router->post('/users/{id}/posts', ['CreatePostController']);

        // Compile and cache
        $this->router->compile($this->tempCachePath);

        // Create fresh router to test cache loading
        $router = new Router();

        $reflection = new ReflectionClass($router);

        // Manually load and hydrate cache
        $cached = require $this->tempCachePath;
        $hydrateCacheMethod = $reflection->getMethod('hydrateCache');
        $hydrateCacheMethod->setAccessible(true);

        $hydrated = $hydrateCacheMethod->invoke($router, $cached);

        $compiledProperty = $reflection->getProperty('compiled');
        $compiledProperty->setAccessible(true);
        $compiledProperty->setValue($router, $hydrated);

        $compiledLoadedProperty = $reflection->getProperty('compiledLoaded');
        $compiledLoadedProperty->setAccessible(true);
        $compiledLoadedProperty->setValue($router, true);

        // Test static route matching
        $match = $router->match('GET', '/users');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals([], $params);

        // Test dynamic route matching
        $match = $router->match('GET', '/users/123');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertEquals(['id' => '123'], $params);

        // Test multi-param route matching
        $match = $router->match('POST', '/users/456/posts');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertEquals(['id' => '456'], $params);
    }

    public function testPathNormalization(): void
    {
        $this->router->get('/users', ['UsersController']);

        // Test various path formats all match the same route
        $this->assertNotNull($this->router->match('GET', '/users'));
        $this->assertNotNull($this->router->match('GET', '/users/'));
        $this->assertNotNull($this->router->match('GET', '//users'));
        $this->assertNotNull($this->router->match('GET', 'users'));
    }

    public function testAllowedMethodsWithCompiledRoutes(): void
    {
        $this->router->get('/users', ['GetUsersController']);
        $this->router->post('/users', ['CreateUserController']);
        $this->router->put('/users', ['UpdateUserController']);

        $this->router->compile($this->tempCachePath);

        // Create fresh router and let it load the cache properly
        $router = new Router();

        $reflection = new ReflectionClass($router);

        // Force it to load from our test cache path
        $getCachePathMethod = $reflection->getMethod('getCachePath');
        $getCachePathMethod->setAccessible(true);

        // Manually load and hydrate cache
        $cached = require $this->tempCachePath;
        $hydrateCacheMethod = $reflection->getMethod('hydrateCache');
        $hydrateCacheMethod->setAccessible(true);

        $hydrated = $hydrateCacheMethod->invoke($router, $cached);

        $compiledProperty = $reflection->getProperty('compiled');
        $compiledProperty->setAccessible(true);
        $compiledProperty->setValue($router, $hydrated);

        $compiledLoadedProperty = $reflection->getProperty('compiledLoaded');
        $compiledLoadedProperty->setAccessible(true);
        $compiledLoadedProperty->setValue($router, true);

        $allowed = $router->allowedMethodsForPath('/users');
        $this->assertCount(4, $allowed); // GET, POST, PUT, HEAD (auto-added)
        $this->assertContains('GET', $allowed);
        $this->assertContains('POST', $allowed);
        $this->assertContains('PUT', $allowed);
        $this->assertContains('HEAD', $allowed); // HEAD auto-added for GET routes
    }

    public function testRoutePrioritySorting(): void
    {
        // Register routes in random order
        $this->router->get('/users/{id}', ['ShowUserController']);
        $this->router->get('/users/me', ['CurrentUserController']); // Should match first
        $this->router->get('/users/{id}/posts', ['UserPostsController']);

        $compiler = new RouteCompiler();
        $compiled = $compiler->compile($this->router->getRoutes());

        // In compiled form, more specific routes should come first
        $this->assertNotEmpty($compiled['dynamic']['GET']);

        // Note: '/users/me' is static, so it goes in static map
        $this->assertArrayHasKey('/users/me', $compiled['static']['GET']);
    }

    public function testMiddlewarePreservation(): void
    {
        // Register route with middleware
        $this->router->get('/protected', [
            'AuthMiddleware',
            'RateLimitMiddleware',
            'ProtectedController'
        ]);

        $compiler = new RouteCompiler();
        $compiled = $compiler->compile($this->router->getRoutes());

        /** @var CompiledRoute $route */
        $route = $compiled['static']['GET']['/protected'];

        $this->assertEquals(['AuthMiddleware', 'RateLimitMiddleware'], $route->middlewares);
        $this->assertEquals('ProtectedController', $route->controller);
    }

    public function testParameterConstraints(): void
    {
        // Register route with constrained parameter
        $this->router->get('/users/{id:\d+}', ['ShowUserController']);

        $compiler = new RouteCompiler();
        $compiled = $compiler->compile($this->router->getRoutes());

        /** @var CompiledRoute $route */
        $route = $compiled['dynamic']['GET'][0];

        // Should have a constraint at segment index 1 (the {id} segment)
        $this->assertNotEmpty($route->paramConstraints);
        $this->assertArrayHasKey(1, $route->paramConstraints);
        $this->assertIsString($route->paramConstraints[1]);
        $this->assertStringContainsString('\d+', $route->paramConstraints[1]);
    }

    public function testCompiledRouteMatchingWithConstraints(): void
    {
        new RouteCompiler();

        // Create route with digit-only constraint
        $route = new CompiledRoute(
            method: 'GET',
            path: '/users/{id}',
            segments: ['users', '{id}'],
            paramNames: ['id'],
            paramConstraints: [1 => '/^\d+$/'], // Segment 1 must be digits
            middlewares: [],
            controller: 'UserController',
            isStatic: false
        );

        // Should match numeric IDs
        $result = $route->matchSegments(['users', '123']);
        $this->assertNotNull($result);
        $this->assertEquals(['id' => '123'], $result);

        // Should NOT match non-numeric IDs
        $result = $route->matchSegments(['users', 'abc']);
        $this->assertNull($result);
    }

    public function testCacheClearance(): void
    {
        $this->router->get('/test', ['TestController']);
        $this->router->compile($this->tempCachePath);

        $this->assertFileExists($this->tempCachePath);

        $cleared = $this->router->clearCache($this->tempCachePath);
        $this->assertTrue($cleared);
        $this->assertFileDoesNotExist($this->tempCachePath);
    }

    public function testBackwardsCompatibilityWithoutCache(): void
    {
        // Register routes normally
        $this->router->get('/users', ['UsersController']);
        $this->router->get('/users/{id}', ['ShowUserController']);
        $this->router->post('/users', ['CreateUserController']);

        // Don't compile - should work with traditional matching
        $match = $this->router->match('GET', '/users');
        $this->assertNotNull($match);

        $match = $this->router->match('GET', '/users/123');
        $this->assertNotNull($match);
        $this->assertEquals(['id' => '123'], $match[1]);

        $match = $this->router->match('POST', '/users');
        $this->assertNotNull($match);
    }

    public function testMethodFirstIndexing(): void
    {
        $this->router->get('/users', ['UsersController']);
        $this->router->post('/posts', ['PostsController']);

        $compiler = new RouteCompiler();
        $compiled = $compiler->compile($this->router->getRoutes());

        // Verify methods index
        $this->assertArrayHasKey('methods', $compiled);
        $this->assertContains('GET', $compiled['methods']);
        $this->assertContains('POST', $compiled['methods']);
        $this->assertNotContains('DELETE', $compiled['methods']);
    }

    public function testEmptyRouteCompilation(): void
    {
        // Compile with no routes
        $compiler = new RouteCompiler();
        $compiled = $compiler->compile([]);

        $this->assertIsArray($compiled);
        $this->assertArrayHasKey('static', $compiled);
        $this->assertArrayHasKey('dynamic', $compiled);
        $this->assertArrayHasKey('methods', $compiled);
        $this->assertEmpty($compiled['methods']);
    }

    public function testMultipleParametersInRoute(): void
    {
        $this->router->get('/users/{userId}/posts/{postId}/comments/{commentId}', [
            'ShowCommentController'
        ]);

        $compiler = new RouteCompiler();
        $compiled = $compiler->compile($this->router->getRoutes());

        /** @var CompiledRoute $route */
        $route = $compiled['dynamic']['GET'][0];

        $this->assertCount(3, $route->paramNames);
        $this->assertEquals(['userId', 'postId', 'commentId'], $route->paramNames);

        // Test matching
        $params = $route->matchSegments(['users', '1', 'posts', '2', 'comments', '3']);
        $this->assertNotNull($params);
        $this->assertEquals([
            'userId' => '1',
            'postId' => '2',
            'commentId' => '3'
        ], $params);
    }

    public function testOpcacheFriendlyExport(): void
    {
        $this->router->get('/test', ['TestController']);
        $this->router->compile($this->tempCachePath);

        // Verify exported code is valid PHP
        $content = file_get_contents($this->tempCachePath);
        $this->assertStringStartsWith('<?php', $content);
        $this->assertStringContainsString('return', $content);

        // Verify it can be required multiple times (opcache safe)
        $data1 = require $this->tempCachePath;
        $data2 = require $this->tempCachePath;

        $this->assertEquals($data1, $data2);
    }

    public function testHeadToGetFallback(): void
    {
        // Register only GET route
        $this->router->get('/users', ['UsersController']);

        // HEAD should work via fallback
        $match = $this->router->match('HEAD', '/users');
        $this->assertNotNull($match);
        [$route, $params] = $match;
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals([], $params);
    }

    public function testHeadToGetFallbackWithCompilation(): void
    {
        // Register only GET route
        $this->router->get('/api/data', ['DataController']);
        
        $this->router->compile($this->tempCachePath);

        // Load cached data to verify HEAD was added
        $cached = require $this->tempCachePath;
        
        // Verify HEAD route was created from GET
        $this->assertArrayHasKey('HEAD', $cached['static']);
        $this->assertArrayHasKey('/api/data', $cached['static']['HEAD']);
        
        // Verify methods list includes HEAD
        $this->assertContains('HEAD', $cached['methods']);
    }

    public function testPrecomputedAllowedMethods(): void
    {
        // Register routes with GET
        $this->router->get('/health', ['HealthController']);
        $this->router->post('/users', ['CreateUserController']);
        
        $this->router->compile($this->tempCachePath);

        // Load cache to check precomputed allowed methods
        $cached = require $this->tempCachePath;
        
        // Verify allowedMethods are precomputed
        $this->assertArrayHasKey('allowedMethods', $cached);
        $this->assertArrayHasKey('/health', $cached['allowedMethods']);
        
        // GET routes should automatically include HEAD
        $this->assertContains('GET', $cached['allowedMethods']['/health']);
        $this->assertContains('HEAD', $cached['allowedMethods']['/health']);
        
        // POST-only routes should not include HEAD
        $this->assertContains('POST', $cached['allowedMethods']['/users']);
        $this->assertNotContains('HEAD', $cached['allowedMethods']['/users']);
    }

    public function testAllowedMethodsIncludesHead(): void
    {
        // Register GET route
        $this->router->get('/api/resource', ['ResourceController']);
        
        // Check allowed methods includes HEAD
        $allowed = $this->router->allowedMethodsForPath('/api/resource');
        
        $this->assertContains('GET', $allowed);
        $this->assertContains('HEAD', $allowed);
    }
}

