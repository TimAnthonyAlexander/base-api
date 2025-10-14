<?php

declare(strict_types=1);

namespace Tests;

use BaseApi\Http\StreamedResponse;
use BaseApi\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for StreamedResponse
 */
class StreamedResponseTest extends TestCase
{
    public function test_constructor_accepts_callable(): void
    {
        $callback = function(): void {
            echo 'test';
        };

        $response = new StreamedResponse($callback);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->status);
    }

    public function test_constructor_with_custom_status(): void
    {
        $callback = function(): void {
            echo 'test';
        };

        $response = new StreamedResponse($callback, 201);

        $this->assertEquals(201, $response->status);
    }

    public function test_constructor_with_custom_headers(): void
    {
        $callback = function(): void {
            echo 'test';
        };

        $headers = [
            'Content-Type' => 'text/event-stream',
            'X-Custom-Header' => 'custom-value',
        ];

        $response = new StreamedResponse($callback, 200, $headers);

        $this->assertEquals('text/event-stream', $response->headers['Content-Type']);
        $this->assertEquals('custom-value', $response->headers['X-Custom-Header']);
    }

    public function test_get_callback_returns_callable(): void
    {
        $callback = function(): void {
            echo 'test';
        };

        $response = new StreamedResponse($callback);
        $retrievedCallback = $response->getCallback();

        $this->assertIsCallable($retrievedCallback);
        $this->assertSame($callback, $retrievedCallback);
    }

    public function test_send_content_executes_callback(): void
    {
        $executed = false;
        $callback = function() use (&$executed): void {
            $executed = true;
            echo 'content';
        };

        $response = new StreamedResponse($callback);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertTrue($executed);
        $this->assertEquals('content', $output);
    }

    public function test_is_streamed_returns_true(): void
    {
        $callback = function(): void {};
        $response = new StreamedResponse($callback);

        $this->assertTrue($response->isStreamed());
    }

    public function test_sse_factory_method(): void
    {
        $callback = function(): void {
            echo 'data: test' . "\n\n";
        };

        $response = StreamedResponse::sse($callback);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->status);
        $this->assertEquals('text/event-stream', $response->headers['Content-Type']);
        $this->assertEquals('no-cache', $response->headers['Cache-Control']);
        $this->assertEquals('no', $response->headers['X-Accel-Buffering']);
        $this->assertArrayNotHasKey('Connection', $response->headers);
    }

    public function test_sse_factory_with_custom_status(): void
    {
        $callback = function(): void {};
        $response = StreamedResponse::sse($callback, 202);

        $this->assertEquals(202, $response->status);
    }

    public function test_sse_factory_with_additional_headers(): void
    {
        $callback = function(): void {};
        $additionalHeaders = [
            'X-Custom-Header' => 'custom-value',
            'X-Another-Header' => 'another-value',
        ];

        $response = StreamedResponse::sse($callback, 200, $additionalHeaders);

        $this->assertEquals('custom-value', $response->headers['X-Custom-Header']);
        $this->assertEquals('another-value', $response->headers['X-Another-Header']);
        $this->assertEquals('text/event-stream', $response->headers['Content-Type']);
    }

    public function test_sse_factory_allows_header_override(): void
    {
        $callback = function(): void {};
        $overrideHeaders = [
            'Content-Type' => 'application/custom-stream',
        ];

        $response = StreamedResponse::sse($callback, 200, $overrideHeaders);

        $this->assertEquals('application/custom-stream', $response->headers['Content-Type']);
    }

    public function test_chunked_factory_method(): void
    {
        $callback = function(): void {
            echo 'chunk1';
            echo 'chunk2';
        };

        $response = StreamedResponse::chunked($callback);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->status);
        $this->assertEquals('chunked', $response->headers['Transfer-Encoding']);
    }

    public function test_chunked_factory_with_custom_status(): void
    {
        $callback = function(): void {};
        $response = StreamedResponse::chunked($callback, 206);

        $this->assertEquals(206, $response->status);
    }

    public function test_chunked_factory_with_additional_headers(): void
    {
        $callback = function(): void {};
        $additionalHeaders = [
            'Content-Type' => 'application/octet-stream',
            'X-Custom-Header' => 'custom-value',
        ];

        $response = StreamedResponse::chunked($callback, 200, $additionalHeaders);

        $this->assertEquals('application/octet-stream', $response->headers['Content-Type']);
        $this->assertEquals('custom-value', $response->headers['X-Custom-Header']);
        $this->assertEquals('chunked', $response->headers['Transfer-Encoding']);
    }

    public function test_callback_can_access_closure_variables(): void
    {
        $data = ['item1', 'item2', 'item3'];
        $callback = function() use ($data): void {
            foreach ($data as $item) {
                echo $item . ',';
            }
        };

        $response = new StreamedResponse($callback);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertEquals('item1,item2,item3,', $output);
    }

    public function test_multiple_calls_to_send_content(): void
    {
        $counter = 0;
        $callback = function() use (&$counter): void {
            $counter++;
            echo 'call-' . $counter;
        };

        $response = new StreamedResponse($callback);

        ob_start();
        $response->sendContent();
        $output1 = ob_get_clean();

        ob_start();
        $response->sendContent();
        $output2 = ob_get_clean();

        $this->assertEquals('call-1', $output1);
        $this->assertEquals('call-2', $output2);
        $this->assertEquals(2, $counter);
    }

    public function test_body_is_empty_string(): void
    {
        $callback = function(): void {
            echo 'streamed content';
        };

        $response = new StreamedResponse($callback);

        $this->assertEquals('', $response->body);
    }

    public function test_inherits_from_response(): void
    {
        $callback = function(): void {};
        $response = new StreamedResponse($callback);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_sse_streaming_with_json_events(): void
    {
        $events = [
            ['type' => 'message', 'data' => 'Hello'],
            ['type' => 'message', 'data' => 'World'],
        ];

        $callback = function() use ($events): void {
            foreach ($events as $event) {
                echo 'data: ' . json_encode($event) . "\n\n";
                flush();
            }
        };

        $response = StreamedResponse::sse($callback);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('data: {"type":"message","data":"Hello"}', $output);
        $this->assertStringContainsString('data: {"type":"message","data":"World"}', $output);
    }

    public function test_callback_with_object_method(): void
    {
        $generator = new class {
            public function generate(): void
            {
                echo 'generated content';
            }
        };

        $response = new StreamedResponse($generator->generate(...));

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertEquals('generated content', $output);
    }

    public function test_with_header_method_still_works(): void
    {
        $callback = function(): void {};
        $response = new StreamedResponse($callback);

        $newResponse = $response->withHeader('X-Test-Header', 'test-value');

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals('test-value', $newResponse->headers['X-Test-Header']);
    }

    public function test_with_status_method_still_works(): void
    {
        $callback = function(): void {};
        $response = new StreamedResponse($callback);

        $newResponse = $response->withStatus(201);

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals(201, $newResponse->status);
        $this->assertEquals(200, $response->status);
    }

    public function test_with_headers_method_still_works(): void
    {
        $callback = function(): void {};
        $response = new StreamedResponse($callback);

        $headers = [
            'X-Header-1' => 'value1',
            'X-Header-2' => 'value2',
        ];

        $newResponse = $response->withHeaders($headers);

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals('value1', $newResponse->headers['X-Header-1']);
        $this->assertEquals('value2', $newResponse->headers['X-Header-2']);
    }

    public function test_openai_streaming_use_case(): void
    {
        // Simulate OpenAI streaming response
        $chunks = [
            ['delta' => 'Hello'],
            ['delta' => ' world'],
            ['delta' => '!'],
        ];

        $callback = function() use ($chunks): void {
            foreach ($chunks as $chunk) {
                echo 'data: ' . json_encode($chunk) . "\n\n";
                flush();
            }

            echo "data: [DONE]\n\n";
        };

        $response = StreamedResponse::sse($callback);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('data: {"delta":"Hello"}', $output);
        $this->assertStringContainsString('data: {"delta":" world"}', $output);
        $this->assertStringContainsString('data: {"delta":"!"}', $output);
        $this->assertStringContainsString('data: [DONE]', $output);
    }
}

