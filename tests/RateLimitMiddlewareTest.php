<?php

namespace BaseApi\Tests;

use Exception;
use Override;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use BaseApi\Http\Middleware\RateLimitMiddleware;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\JsonResponse;
use BaseApi\App;

class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $middleware;

    private string $tempDir;

    #[Override]
    protected function setUp(): void
    {
        // Create a temporary directory for rate limit files with more entropy for parallel tests
        $this->tempDir = sys_get_temp_dir() . '/ratelimit-test-' . uniqid(getmypid() . '-', true);
        mkdir($this->tempDir, 0755, true);

        // Set environment variable for rate limit directory BEFORE creating mock
        $_ENV['RATE_LIMIT_DIR'] = $this->tempDir;

        // Mock App::basePath to avoid dependency on App being booted
        // Only create mock if App doesn't exist at all
        if (!class_exists(App::class, false)) {
            $this->createMockAppClass();
        }

        $this->middleware = new RateLimitMiddleware();
    }

    #[Override]
    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->rmdirRecursive($this->tempDir);
        }

        // Clean up environment
        unset($_ENV['RATE_LIMIT_DIR']);
        
        // Clean up session
        if (isset($_SESSION['user_id'])) {
            unset($_SESSION['user_id']);
        }
    }

    public function testHandleWithoutLimitOption(): void
    {
        $request = $this->createRequest();
        $response = new Response(200, [], 'OK');
        $next = fn($req): Response => $response;

        // No limit option set
        $result = $this->middleware->handle($request, $next);

        $this->assertSame($response, $result);
    }

    public function testHandleWithInvalidLimitFormat(): void
    {
        $this->middleware->setOptions(['limit' => 'invalid_format']);

        $request = $this->createRequest();
        $response = new Response(200, [], 'OK');
        $next = fn($req): Response => $response;

        $result = $this->middleware->handle($request, $next);

        $this->assertSame($response, $result);
    }

    public function testHandleWithinLimit(): void
    {
        $this->middleware->setOptions(['limit' => '10/1m']);

        $request = $this->createRequest();
        $response = new Response(200, [], 'OK');
        $next = fn($req): Response => $response;

        $result = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->status);

        // Check rate limit headers
        $this->assertArrayHasKey('X-RateLimit-Limit', $result->headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $result->headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $result->headers);

        $this->assertEquals('10', $result->headers['X-RateLimit-Limit']);
        $this->assertEquals('9', $result->headers['X-RateLimit-Remaining']);
    }

    public function testHandleExceedsLimit(): void
    {
        $this->middleware->setOptions(['limit' => '1/1m']);

        $request = $this->createRequest();
        $next = fn($req): Response => new Response(200, [], 'OK');

        // First request should pass
        $result1 = $this->middleware->handle($request, $next);
        $this->assertEquals(200, $result1->status);

        // Second request should be rate limited
        $result2 = $this->middleware->handle($request, $next);
        $this->assertInstanceOf(JsonResponse::class, $result2);
        $this->assertEquals(429, $result2->status);

        // Check rate limit headers
        $this->assertArrayHasKey('X-RateLimit-Limit', $result2->headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $result2->headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $result2->headers);
        $this->assertArrayHasKey('Retry-After', $result2->headers);

        $this->assertEquals('1', $result2->headers['X-RateLimit-Limit']);
        $this->assertEquals('0', $result2->headers['X-RateLimit-Remaining']);
    }

    public function testHandleWithSessionUserId(): void
    {
        $_SESSION['user_id'] = '123';

        $this->middleware->setOptions(['limit' => '5/1m']);

        $request = $this->createRequest();
        $response = new Response(200, [], 'OK');
        $next = fn($req): Response => $response;

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $result->status);
        $this->assertEquals('4', $result->headers['X-RateLimit-Remaining']);

        unset($_SESSION['user_id']);
    }

    public function testHandlePreservesRequestId(): void
    {
        $this->middleware->setOptions(['limit' => '1/1m']);

        $request = $this->createRequest();
        $request->requestId = 'test-request-123';
        $next = fn($req): Response => new Response(200, [], 'OK');

        // First request to set up rate limit
        $this->middleware->handle($request, $next);

        // Second request should be rate limited and preserve request ID
        $result = $this->middleware->handle($request, $next);
        $this->assertEquals(429, $result->status);
        $this->assertEquals('test-request-123', $result->headers['X-Request-Id']);
    }

    public function testParseLimitValidFormats(): void
    {
        $reflection = new ReflectionClass($this->middleware);
        $parseLimit = $reflection->getMethod('parseLimit');
        $parseLimit->setAccessible(true);

        $testCases = [
            '10/1s' => [10, 1],
            '100/5m' => [100, 300],
            '1000/1h' => [1000, 3600],
            '5000/1d' => [5000, 86400],
            '50/30s' => [50, 30],
        ];

        foreach ($testCases as $input => $expected) {
            $result = $parseLimit->invoke($this->middleware, $input);
            $this->assertEquals($expected, $result, 'Failed for limit: ' . $input);
        }
    }

    public function testParseLimitInvalidFormats(): void
    {
        $reflection = new ReflectionClass($this->middleware);
        $parseLimit = $reflection->getMethod('parseLimit');
        $parseLimit->setAccessible(true);

        $invalidFormats = [
            'invalid',
            '10/1x',
            'abc/1m',
            '10/',
            '/1m',
            '10-1m',
            '10 per minute',
        ];

        foreach ($invalidFormats as $input) {
            $result = $parseLimit->invoke($this->middleware, $input);
            $this->assertNull($result, 'Should return null for invalid format: ' . $input);
        }
    }

    public function testGetWindowStart(): void
    {
        $reflection = new ReflectionClass($this->middleware);
        $getWindowStart = $reflection->getMethod('getWindowStart');
        $getWindowStart->setAccessible(true);

        // Test window alignment
        $windowSeconds = 60; // 1 minute window
        $result = $getWindowStart->invoke($this->middleware, $windowSeconds);

        // Result should be aligned to the minute boundary
        $this->assertEquals(0, $result % $windowSeconds);
        $this->assertLessThanOrEqual(time(), $result);
        $this->assertGreaterThan(time() - $windowSeconds, $result);
    }

    public function testGetRateLimitKeyWithoutUser(): void
    {
        $reflection = new ReflectionClass($this->middleware);
        $getRateLimitKey = $reflection->getMethod('getRateLimitKey');
        $getRateLimitKey->setAccessible(true);

        $request = $this->createRequest();
        $result = $getRateLimitKey->invoke($this->middleware, $request);

        $this->assertStringStartsWith('ip:', $result);
    }

    public function testGetRateLimitKeyWithUser(): void
    {
        $_SESSION['user_id'] = 'user123';

        $reflection = new ReflectionClass($this->middleware);
        $getRateLimitKey = $reflection->getMethod('getRateLimitKey');
        $getRateLimitKey->setAccessible(true);

        $request = $this->createRequest();
        $result = $getRateLimitKey->invoke($this->middleware, $request);

        $this->assertEquals('user:user123', $result);

        unset($_SESSION['user_id']);
    }

    public function testGetRouteId(): void
    {
        $reflection = new ReflectionClass($this->middleware);
        $getRouteId = $reflection->getMethod('getRouteId');
        $getRouteId->setAccessible(true);

        // Test with route pattern
        $request = $this->createRequest();
        $request->routeMethod = 'POST';
        $request->routePattern = '/api/users/{id}';

        $result = $getRouteId->invoke($this->middleware, $request);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);

        // Test without route pattern (fallback to path)
        $request2 = $this->createRequest();
        $result2 = $getRouteId->invoke($this->middleware, $request2);
        $this->assertIsString($result2);
        $this->assertNotEmpty($result2);
    }

    public function testCorsHeadersWithOrigin(): void
    {
        $this->middleware->setOptions(['limit' => '1/1m']);

        $request = $this->createRequest();
        $request->headers['Origin'] = 'https://example.com';

        $next = fn($req): Response => new Response(200, [], 'OK');

        // First request to set up, second to trigger rate limit
        $this->middleware->handle($request, $next);
        $result = $this->middleware->handle($request, $next);

        // Should return 429 and not crash when processing CORS headers
        $this->assertEquals(429, $result->status);
        // The actual CORS behavior depends on app config, so we just test it doesn't crash
        $this->assertArrayHasKey('X-RateLimit-Limit', $result->headers);
    }


    public function testDifferentLimitFormats(): void
    {
        $testCases = [
            '10/1s' => [10, 1],
            '100/30s' => [100, 30],
            '1000/5m' => [1000, 300],
            '10000/2h' => [10000, 7200],
            '50000/7d' => [50000, 604800],
        ];

        $counter = 0;
        foreach ($testCases as $limitString => [$expectedRequests]) {
            $counter++;
            $this->middleware->setOptions(['limit' => $limitString]);

            // Create unique request for each test case to avoid rate limit collisions
            $request = $this->createRequestWithPath('/api/test-' . $counter);
            $response = new Response(200, [], 'OK');
            $next = fn($req): Response => $response;

            $result = $this->middleware->handle($request, $next);

            $this->assertEquals($expectedRequests, (int) $result->headers['X-RateLimit-Limit']);
            $this->assertEquals($expectedRequests - 1, (int) $result->headers['X-RateLimit-Remaining']);
        }
    }

    private function createRequest(): Request
    {
        return $this->createRequestWithPath('/api/test');
    }

    private function createRequestWithPath(string $path): Request
    {
        return new Request(
            'GET',
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


    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = sprintf('%s/%s', $dir, $file);
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * Create a mock App class for testing without using eval()
     */
    private function createMockAppClass(): void
    {
        $mockClassContent = <<<'PHP'
<?php
namespace BaseApi {
    class MockConfig {
        public function list(string $key): array {
            return match($key) {
                'CORS_ALLOWLIST' => ['*'],
                default => []
            };
        }
    }
    
    class App {
        public static function basePath($path = ""): string 
        { 
            return "/tmp" . ($path ? "/" . ltrim($path, "/") : ""); 
        }
        
        public static function config(string $key = '', mixed $default = null): mixed
        {
            if ($key === '' || $key === '0') {
                return new MockConfig();
            }
            
            // Return values based on key
            return match($key) {
                'rate_limit.dir' => $_ENV['RATE_LIMIT_DIR'] ?? 'storage/ratelimits',
                'rate_limit.trust_proxy' => false,
                default => $default
            };
        }
    }
}
PHP;

        // Fix temp file leak: tempnam() creates a file, rename it to avoid leak
        $tempFile = tempnam(sys_get_temp_dir(), 'mock_app_');
        if ($tempFile === false) {
            throw new Exception('Failed to create temporary file');
        }

        $phpTempFile = $tempFile . '.php';
        if (!rename($tempFile, $phpTempFile)) {
            @unlink($tempFile); // Clean up original if rename fails
            throw new Exception('Failed to rename temporary file');
        }
        
        if (file_put_contents($phpTempFile, $mockClassContent) === false) {
            @unlink($phpTempFile);
            throw new Exception('Failed to create mock App class file');
        }

        try {
            require_once $phpTempFile;
        } finally {
            // Always clean up the temporary file
            @unlink($phpTempFile);
        }
    }
}

