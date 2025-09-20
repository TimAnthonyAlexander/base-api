<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Http\Request;

class RequestTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $method = 'GET';
        $path = '/api/users';
        $headers = ['Content-Type' => 'application/json'];
        $query = ['page' => 1, 'limit' => 10];
        $body = ['name' => 'John Doe'];
        $rawBody = '{"name": "John Doe"}';
        $files = ['avatar' => ['tmp_name' => '/tmp/upload']];
        $cookies = ['session' => 'abc123'];
        $session = ['user_id' => 42];
        $requestId = 'req-123';

        $request = new Request(
            $method,
            $path,
            $headers,
            $query,
            $body,
            $rawBody,
            $files,
            $cookies,
            $session,
            $requestId
        );

        $this->assertEquals($method, $request->method);
        $this->assertEquals($path, $request->path);
        $this->assertEquals($headers, $request->headers);
        $this->assertEquals($query, $request->query);
        $this->assertEquals($body, $request->body);
        $this->assertEquals($rawBody, $request->rawBody);
        $this->assertEquals($files, $request->files);
        $this->assertEquals($cookies, $request->cookies);
        $this->assertEquals($session, $request->session);
        $this->assertEquals($requestId, $request->requestId);
    }

    public function testDefaultProperties(): void
    {
        $request = new Request(
            'POST',
            '/api/test',
            [],
            [],
            [],
            null,
            [],
            [],
            [],
            'test-id'
        );

        // Test default values for optional properties
        $this->assertNull($request->user);
        $this->assertEquals([], $request->pathParams);
        $this->assertEquals([], $request->allowedMethods);
        $this->assertNull($request->routePattern);
        $this->assertNull($request->routeMethod);
        $this->assertNull($request->startTime);
    }

    public function testPropertyAssignment(): void
    {
        $request = new Request(
            'GET',
            '/test',
            [],
            [],
            [],
            null,
            [],
            [],
            [],
            'test-id'
        );

        // Test that we can assign to public properties
        $request->user = ['id' => 1, 'email' => 'test@example.com'];
        $request->pathParams = ['id' => '123'];
        $request->allowedMethods = ['GET', 'POST'];
        $request->routePattern = '/users/{id}';
        $request->routeMethod = 'GET';
        $request->startTime = microtime(true);

        $this->assertEquals(['id' => 1, 'email' => 'test@example.com'], $request->user);
        $this->assertEquals(['id' => '123'], $request->pathParams);
        $this->assertEquals(['GET', 'POST'], $request->allowedMethods);
        $this->assertEquals('/users/{id}', $request->routePattern);
        $this->assertEquals('GET', $request->routeMethod);
        $this->assertIsFloat($request->startTime);
    }

    public function testHttpMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

        foreach ($methods as $method) {
            $request = new Request(
                $method,
                '/test',
                [],
                [],
                [],
                null,
                [],
                [],
                [],
                'test-id'
            );

            $this->assertEquals($method, $request->method);
        }
    }

    public function testVariousPathFormats(): void
    {
        $paths = [
            '/',
            '/api',
            '/api/users',
            '/api/users/123',
            '/api/users/{id}',
            '/api/v1/users/{id}/posts/{post_id}',
            '/api/complex-path_with-symbols'
        ];

        foreach ($paths as $path) {
            $request = new Request(
                'GET',
                $path,
                [],
                [],
                [],
                null,
                [],
                [],
                [],
                'test-id'
            );

            $this->assertEquals($path, $request->path);
        }
    }

    public function testHeadersHandling(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer token123',
            'X-Custom-Header' => 'custom-value',
            'Accept' => 'application/json, text/plain',
            'User-Agent' => 'TestClient/1.0'
        ];

        $request = new Request(
            'GET',
            '/test',
            $headers,
            [],
            [],
            null,
            [],
            [],
            [],
            'test-id'
        );

        $this->assertEquals($headers, $request->headers);
        $this->assertEquals('application/json', $request->headers['Content-Type']);
        $this->assertEquals('Bearer token123', $request->headers['Authorization']);
    }

    public function testQueryParametersHandling(): void
    {
        $query = [
            'page' => 1,
            'limit' => 10,
            'sort' => 'name',
            'filter' => ['status' => 'active', 'category' => 'tech'],
            'search' => 'test query',
            'bool_param' => true,
            'null_param' => null
        ];

        $request = new Request(
            'GET',
            '/test',
            [],
            $query,
            [],
            null,
            [],
            [],
            [],
            'test-id'
        );

        $this->assertEquals($query, $request->query);
        $this->assertEquals(1, $request->query['page']);
        $this->assertEquals(['status' => 'active', 'category' => 'tech'], $request->query['filter']);
        $this->assertTrue($request->query['bool_param']);
        $this->assertNull($request->query['null_param']);
    }

    public function testBodyDataHandling(): void
    {
        $body = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true
            ],
            'tags' => ['developer', 'php', 'api']
        ];

        $request = new Request(
            'POST',
            '/test',
            [],
            [],
            $body,
            null,
            [],
            [],
            [],
            'test-id'
        );

        $this->assertEquals($body, $request->body);
        $this->assertEquals('John Doe', $request->body['name']);
        $this->assertEquals(['theme' => 'dark', 'notifications' => true], $request->body['preferences']);
        $this->assertEquals(['developer', 'php', 'api'], $request->body['tags']);
    }

    public function testRawBodyHandling(): void
    {
        $rawBody = '{"name": "John Doe", "email": "john@example.com", "age": 30}';

        $request = new Request(
            'POST',
            '/test',
            [],
            [],
            [],
            $rawBody,
            [],
            [],
            [],
            'test-id'
        );

        $this->assertEquals($rawBody, $request->rawBody);
    }

    public function testNullRawBody(): void
    {
        $request = new Request(
            'GET',
            '/test',
            [],
            [],
            [],
            null,
            [],
            [],
            [],
            'test-id'
        );

        $this->assertNull($request->rawBody);
    }

    public function testFilesHandling(): void
    {
        $files = [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpupload123',
                'size' => 12345,
                'error' => UPLOAD_ERR_OK
            ],
            'documents' => [
                [
                    'name' => 'doc1.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpupload124',
                    'size' => 54321,
                    'error' => UPLOAD_ERR_OK
                ],
                [
                    'name' => 'doc2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpupload125',
                    'size' => 67890,
                    'error' => UPLOAD_ERR_OK
                ]
            ]
        ];

        $request = new Request(
            'POST',
            '/test',
            [],
            [],
            [],
            null,
            $files,
            [],
            [],
            'test-id'
        );

        $this->assertEquals($files, $request->files);
        $this->assertEquals('avatar.jpg', $request->files['avatar']['name']);
        $this->assertEquals('doc1.pdf', $request->files['documents'][0]['name']);
        $this->assertEquals('doc2.pdf', $request->files['documents'][1]['name']);
    }

    public function testCookiesHandling(): void
    {
        $cookies = [
            'session_id' => 'sess_123456789',
            'user_preferences' => 'theme=dark&lang=en',
            'csrf_token' => 'token_abcdef',
            'remember_token' => null
        ];

        $request = new Request(
            'GET',
            '/test',
            [],
            [],
            [],
            null,
            [],
            $cookies,
            [],
            'test-id'
        );

        $this->assertEquals($cookies, $request->cookies);
        $this->assertEquals('sess_123456789', $request->cookies['session_id']);
        $this->assertNull($request->cookies['remember_token']);
    }

    public function testSessionHandling(): void
    {
        $session = [
            'user_id' => 42,
            'username' => 'johndoe',
            'last_login' => '2023-12-01 10:30:00',
            'permissions' => ['read', 'write', 'admin'],
            'cart' => [
                'items' => ['item1', 'item2'],
                'total' => 99.99
            ],
            'flash_messages' => ['success' => 'User created successfully']
        ];

        $request = new Request(
            'GET',
            '/test',
            [],
            [],
            [],
            null,
            [],
            [],
            $session,
            'test-id'
        );

        $this->assertEquals($session, $request->session);
        $this->assertEquals(42, $request->session['user_id']);
        $this->assertEquals(['read', 'write', 'admin'], $request->session['permissions']);
        $this->assertEquals(['items' => ['item1', 'item2'], 'total' => 99.99], $request->session['cart']);
    }

    public function testRequestIdHandling(): void
    {
        $requestIds = [
            'simple-id',
            'uuid-v4-12345678-1234-1234-1234-123456789012',
            'timestamp-1699876543',
            'complex-id_with-symbols.123'
        ];

        foreach ($requestIds as $requestId) {
            $request = new Request(
                'GET',
                '/test',
                [],
                [],
                [],
                null,
                [],
                [],
                [],
                $requestId
            );

            $this->assertEquals($requestId, $request->requestId);
        }
    }

    public function testEmptyArraysHandling(): void
    {
        $request = new Request(
            'GET',
            '/test',
            [],      // empty headers
            [],      // empty query
            [],      // empty body
            null,    // no raw body
            [],      // empty files
            [],      // empty cookies
            [],      // empty session
            'test-id'
        );

        $this->assertEquals([], $request->headers);
        $this->assertEquals([], $request->query);
        $this->assertEquals([], $request->body);
        $this->assertEquals([], $request->files);
        $this->assertEquals([], $request->cookies);
        $this->assertEquals([], $request->session);
    }
}
