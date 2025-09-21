<?php

namespace BaseApi\Tests;

use Override;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use BaseApi\Http\JsonResponse;
use BaseApi\Http\Response;
use BaseApi\Database\PaginatedResult;
use BaseApi\Logger;

class JsonResponseTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        // Reset the Logger's request ID for testing
        Logger::setRequestId('test-request-id-123');
    }

    #[Override]
    protected function tearDown(): void
    {
        // Reset the Logger's request ID - use reflection to reset static property
        $reflection = new ReflectionClass(Logger::class);
        $requestIdProperty = $reflection->getProperty('requestId');
        $requestIdProperty->setAccessible(true);
        $requestIdProperty->setValue(null, null);
    }

    public function testBasicConstruction(): void
    {
        $data = ['message' => 'Hello World'];
        $response = new JsonResponse($data, 200);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('application/json; charset=utf-8', $response->headers['Content-Type']);
        $this->assertEquals(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $response->body);
    }

    public function testConstructionWithHeaders(): void
    {
        $data = ['message' => 'Hello World'];
        $customHeaders = ['X-Custom-Header' => 'custom-value'];
        $response = new JsonResponse($data, 201, $customHeaders);

        $this->assertEquals(201, $response->status);
        $this->assertEquals('application/json; charset=utf-8', $response->headers['Content-Type']);
        $this->assertEquals('custom-value', $response->headers['X-Custom-Header']);
    }

    public function testOkMethod(): void
    {
        $payload = ['id' => 1, 'name' => 'Test'];
        $response = JsonResponse::ok($payload);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('application/json; charset=utf-8', $response->headers['Content-Type']);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($payload, $decodedBody['data']);
    }

    public function testOkMethodWithCustomStatus(): void
    {
        $payload = ['id' => 1, 'name' => 'Test'];
        $response = JsonResponse::ok($payload, 201);

        $this->assertEquals(201, $response->status);
        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($payload, $decodedBody['data']);
    }

    public function testCreatedMethod(): void
    {
        $payload = ['id' => 1, 'name' => 'Test'];
        $response = JsonResponse::created($payload);

        $this->assertEquals(201, $response->status);
        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($payload, $decodedBody['data']);
    }

    public function testNoContentMethod(): void
    {
        $response = JsonResponse::noContent();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->status);
    }

    public function testBadRequestMethod(): void
    {
        $message = 'Invalid input';
        $errors = ['field' => ['error1', 'error2']];
        $response = JsonResponse::badRequest($message, $errors);

        $this->assertEquals(400, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals($errors, $decodedBody['errors']);
        $this->assertEquals('test-request-id-123', $decodedBody['requestId']);
    }

    public function testBadRequestMethodWithoutErrors(): void
    {
        $message = 'Invalid input';
        $response = JsonResponse::badRequest($message);

        $this->assertEquals(400, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertArrayNotHasKey('errors', $decodedBody);
        $this->assertEquals('test-request-id-123', $decodedBody['requestId']);
    }

    public function testUnauthorizedMethod(): void
    {
        $response = JsonResponse::unauthorized();

        $this->assertEquals(401, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals('Unauthorized', $decodedBody['error']);
        $this->assertEquals('test-request-id-123', $decodedBody['requestId']);
    }

    public function testUnauthorizedMethodWithCustomMessage(): void
    {
        $message = 'Custom unauthorized message';
        $response = JsonResponse::unauthorized($message);

        $this->assertEquals(401, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($message, $decodedBody['error']);
    }

    public function testNotFoundMethod(): void
    {
        $response = JsonResponse::notFound();

        $this->assertEquals(404, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals('Not Found', $decodedBody['error']);
        $this->assertEquals('test-request-id-123', $decodedBody['requestId']);
    }

    public function testNotFoundMethodWithCustomMessage(): void
    {
        $message = 'Custom not found message';
        $response = JsonResponse::notFound($message);

        $this->assertEquals(404, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($message, $decodedBody['error']);
    }

    public function testErrorMethod(): void
    {
        $response = JsonResponse::error();

        $this->assertEquals(500, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals('Server Error', $decodedBody['error']);
        $this->assertEquals('test-request-id-123', $decodedBody['requestId']);
    }

    public function testErrorMethodWithCustomParameters(): void
    {
        $message = 'Custom error message';
        $status = 503;
        $response = JsonResponse::error($message, $status);

        $this->assertEquals($status, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($message, $decodedBody['error']);
    }

    public function testSuccessMethod(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = JsonResponse::success($data);

        $this->assertEquals(200, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertTrue($decodedBody['success']);
        $this->assertEquals($data, $decodedBody['data']);
        $this->assertArrayHasKey('meta', $decodedBody);
        $this->assertArrayHasKey('timestamp', $decodedBody['meta']);
        $this->assertEquals('test-request-id-123', $decodedBody['meta']['request_id']);
    }

    public function testSuccessMethodWithMeta(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $meta = ['custom' => 'value'];
        $response = JsonResponse::success($data, 201, $meta);

        $this->assertEquals(201, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertTrue($decodedBody['success']);
        $this->assertEquals($data, $decodedBody['data']);
        $this->assertEquals('value', $decodedBody['meta']['custom']);
        $this->assertArrayHasKey('timestamp', $decodedBody['meta']);
    }

    public function testAcceptedMethod(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = JsonResponse::accepted($data);

        $this->assertEquals(202, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertTrue($decodedBody['success']);
        $this->assertEquals($data, $decodedBody['data']);
    }

    public function testValidationErrorMethod(): void
    {
        $errors = ['name' => ['Name is required']];
        $message = 'Custom validation message';
        $response = JsonResponse::validationError($errors, $message);

        $this->assertEquals(422, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertFalse($decodedBody['success']);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals($errors, $decodedBody['errors']);
        $this->assertArrayHasKey('meta', $decodedBody);
        $this->assertEquals('test-request-id-123', $decodedBody['meta']['request_id']);
    }

    public function testValidationErrorMethodWithDefaultMessage(): void
    {
        $errors = ['name' => ['Name is required']];
        $response = JsonResponse::validationError($errors);

        $this->assertEquals(422, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals('Validation failed', $decodedBody['error']);
    }

    public function testForbiddenMethod(): void
    {
        $response = JsonResponse::forbidden();

        $this->assertEquals(403, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertFalse($decodedBody['success']);
        $this->assertEquals('Forbidden', $decodedBody['error']);
        $this->assertEquals('test-request-id-123', $decodedBody['meta']['request_id']);
    }

    public function testForbiddenMethodWithCustomMessage(): void
    {
        $message = 'Custom forbidden message';
        $response = JsonResponse::forbidden($message);

        $this->assertEquals(403, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($message, $decodedBody['error']);
    }

    public function testUnprocessableMethod(): void
    {
        $message = 'Unprocessable entity';
        $response = JsonResponse::unprocessable($message);

        $this->assertEquals(422, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertFalse($decodedBody['success']);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals('test-request-id-123', $decodedBody['meta']['request_id']);
        $this->assertArrayNotHasKey('details', $decodedBody);
    }

    public function testUnprocessableMethodWithDetails(): void
    {
        $message = 'Unprocessable entity';
        $details = ['field1' => 'error1'];
        $response = JsonResponse::unprocessable($message, $details);

        $this->assertEquals(422, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals($details, $decodedBody['details']);
    }

    public function testPaginatedMethod(): void
    {
        $data = [['id' => 1], ['id' => 2]];
        $paginatedResult = new PaginatedResult($data, 1, 10, 20);

        $response = JsonResponse::paginated($paginatedResult);

        $this->assertEquals(200, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertTrue($decodedBody['success']);
        $this->assertEquals($data, $decodedBody['data']);

        $pagination = $decodedBody['pagination'];
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(20, $pagination['total']);
        $this->assertEquals(10, $pagination['remaining']);

        $this->assertArrayHasKey('meta', $decodedBody);
        $this->assertEquals('test-request-id-123', $decodedBody['meta']['request_id']);
    }

    public function testPaginatedMethodWithMeta(): void
    {
        $data = [['id' => 1], ['id' => 2]];
        $paginatedResult = new PaginatedResult($data, 1, 10, 20);
        $meta = ['custom' => 'value'];

        $response = JsonResponse::paginated($paginatedResult, $meta);

        $this->assertEquals(200, $response->status);

        $decodedBody = json_decode((string) $response->body, true);
        $this->assertEquals('value', $decodedBody['meta']['custom']);

        // Should also contain pagination headers
        $this->assertEquals('1', $response->headers['X-Page']);
        $this->assertEquals('10', $response->headers['X-Per-Page']);
        $this->assertEquals('20', $response->headers['X-Total']);
    }

    public function testWithMetaMethod(): void
    {
        $originalData = ['id' => 1, 'name' => 'Test'];
        $response = JsonResponse::success($originalData);

        $additionalMeta = ['custom' => 'value', 'another' => 'meta'];
        $newResponse = $response->withMeta($additionalMeta);

        // Original response should be unchanged
        $this->assertNotSame($response, $newResponse);

        $newDecodedBody = json_decode((string) $newResponse->body, true);
        $this->assertEquals('value', $newDecodedBody['meta']['custom']);
        $this->assertEquals('meta', $newDecodedBody['meta']['another']);
        $this->assertArrayHasKey('timestamp', $newDecodedBody['meta']); // Original meta should be preserved
    }

    public function testWithMetaMethodOnResponseWithoutMeta(): void
    {
        $originalData = ['simple' => 'data'];
        $response = new JsonResponse($originalData);

        $meta = ['custom' => 'value'];
        $newResponse = $response->withMeta($meta);

        $newDecodedBody = json_decode((string) $newResponse->body, true);
        $this->assertEquals('value', $newDecodedBody['meta']['custom']);
    }

    public function testWithHeadersMethod(): void
    {
        $response = JsonResponse::success(['data' => 'test']);
        $headers = ['X-Custom-Header' => 'custom-value', 'X-Another-Header' => 'another-value'];

        $newResponse = $response->withHeaders($headers);

        // Original response should be unchanged
        $this->assertNotSame($response, $newResponse);

        $this->assertEquals('custom-value', $newResponse->headers['X-Custom-Header']);
        $this->assertEquals('another-value', $newResponse->headers['X-Another-Header']);
        $this->assertEquals('application/json; charset=utf-8', $newResponse->headers['Content-Type']);
    }

    public function testJsonEncodingWithUnicodeAndSlashes(): void
    {
        $data = [
            'unicode' => 'Héllo Wörld 🚀',
            'url' => 'https://example.com/path',
            'special' => 'Some "quoted" text & ampersand'
        ];

        $response = new JsonResponse($data);

        // Should not escape unicode or slashes
        $this->assertStringContainsString('Héllo Wörld 🚀', $response->body);
        $this->assertStringContainsString('https://example.com/path', $response->body);
        $this->assertStringNotContainsString('\\/', $response->body);
        $this->assertStringNotContainsString('\\u', $response->body);
    }

    public function testGetCurrentRequestIdWhenNotSet(): void
    {
        // Reset using reflection since setRequestId doesn't accept null
        $reflection = new ReflectionClass(Logger::class);
        $requestIdProperty = $reflection->getProperty('requestId');
        $requestIdProperty->setAccessible(true);
        $requestIdProperty->setValue(null, null);

        $response = JsonResponse::badRequest('Test error');
        $decodedBody = json_decode((string) $response->body, true);

        $this->assertNull($decodedBody['requestId']);
    }
}
