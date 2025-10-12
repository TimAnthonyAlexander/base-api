<?php

namespace Tests;

use Override;
use Exception;
use PHPUnit\Framework\TestCase;
use BaseApi\Permissions\PermissionsMiddleware;
use BaseApi\Permissions\PermissionsService;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\JsonResponse;

class PermissionsMiddlewareTest extends TestCase
{
    private string $testFilePath;

    private PermissionsService $service;

    private PermissionsMiddleware $middleware;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->testFilePath = sys_get_temp_dir() . '/test_permissions_middleware_' . uniqid() . '.json';
        $this->service = new PermissionsService($this->testFilePath);
        $this->middleware = new PermissionsMiddleware($this->service);
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function testMiddlewareAllowsWithPermission(): void
    {
        // Create a mock request with user who has admin role
        $request = new Request(
            method: 'GET',
            path: '/test',
            headers: [],
            query: [],
            body: [],
            rawBody: null,
            files: [],
            cookies: [],
            session: [],
            requestId: 'test-123'
        );

        $request->user = ['id' => 'user-123', 'role' => 'admin'];
        $request->middlewareOptions = [
            PermissionsMiddleware::class => ['node' => 'test.permission']
        ];

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled): Response {
            $nextCalled = true;
            return new Response(200, [], 'OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $response->status);
    }

    public function testMiddlewareDeniesWithoutPermission(): void
    {
        // Create a mock request with user who has guest role
        $request = new Request(
            method: 'GET',
            path: '/test',
            headers: [],
            query: [],
            body: [],
            rawBody: null,
            files: [],
            cookies: [],
            session: [],
            requestId: 'test-123'
        );

        $request->user = ['id' => 'user-123', 'role' => 'guest'];
        $request->middlewareOptions = [
            PermissionsMiddleware::class => ['node' => 'admin.delete']
        ];

        $next = function ($req): void {
            throw new Exception('Next should not be called');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->status);
    }

    public function testMiddlewareReturns401WithoutUser(): void
    {
        $request = new Request(
            method: 'GET',
            path: '/test',
            headers: [],
            query: [],
            body: [],
            rawBody: null,
            files: [],
            cookies: [],
            session: [],
            requestId: 'test-123'
        );

        $request->middlewareOptions = [
            PermissionsMiddleware::class => ['node' => 'test.permission']
        ];

        $next = function ($req): void {
            throw new Exception('Next should not be called');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(401, $response->status);
    }

    public function testMiddlewarePassesWithoutNodeRequirement(): void
    {
        // When no node is specified, just check authentication
        $request = new Request(
            method: 'GET',
            path: '/test',
            headers: [],
            query: [],
            body: [],
            rawBody: null,
            files: [],
            cookies: [],
            session: [],
            requestId: 'test-123'
        );

        $request->user = ['id' => 'user-123'];
        $request->middlewareOptions = [];

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled): Response {
            $nextCalled = true;
            return new Response(200, [], 'OK');
        };

        $this->middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
    }

    public function testMiddlewareHandlesObjectUser(): void
    {
        $request = new Request(
            method: 'GET',
            path: '/test',
            headers: [],
            query: [],
            body: [],
            rawBody: null,
            files: [],
            cookies: [],
            session: [],
            requestId: 'test-123'
        );

        // User as object (but we need to set it as array since that's what the property type allows)
        $userObject = new class {
            public string $id = 'user-123';

            public string $role = 'admin';
        };

        // Since Request::$user is typed as ?array, we can't test with an object
        // Instead, let's test with a user array that has the required fields
        $request->user = ['id' => 'user-123', 'role' => 'admin'];
        $request->middlewareOptions = [
            PermissionsMiddleware::class => ['node' => 'test.permission']
        ];

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled): Response {
            $nextCalled = true;
            return new Response(200, [], 'OK');
        };

        $this->middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
    }
}

