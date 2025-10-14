<?php

namespace BaseApi\Testing;

use BaseApi\Http\JsonResponse;
use PHPUnit\Framework\Assert;

/**
 * Fluent test response for easy assertions
 */
class TestResponse
{
    private readonly array $decodedJson;
    
    public function __construct(private readonly JsonResponse $response)
    {
        $this->decodedJson = json_decode((string) $this->response->body, true) ?? [];
    }
    
    /**
     * Assert response has the given status code
     */
    public function assertStatus(int $expectedStatus): self
    {
        Assert::assertEquals(
            $expectedStatus,
            $this->response->status,
            sprintf('Expected status code %d, got %d', $expectedStatus, $this->response->status)
        );
        
        return $this;
    }
    
    /**
     * Assert response is OK (200)
     */
    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }
    
    /**
     * Assert response is Created (201)
     */
    public function assertCreated(): self
    {
        return $this->assertStatus(201);
    }
    
    /**
     * Assert response is No Content (204)
     */
    public function assertNoContent(): self
    {
        return $this->assertStatus(204);
    }
    
    /**
     * Assert response is Bad Request (400)
     */
    public function assertBadRequest(): self
    {
        return $this->assertStatus(400);
    }
    
    /**
     * Assert response is Unauthorized (401)
     */
    public function assertUnauthorized(): self
    {
        return $this->assertStatus(401);
    }
    
    /**
     * Assert response is Forbidden (403)
     */
    public function assertForbidden(): self
    {
        return $this->assertStatus(403);
    }
    
    /**
     * Assert response is Not Found (404)
     */
    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }
    
    /**
     * Assert response is Unprocessable Entity (422)
     */
    public function assertUnprocessable(): self
    {
        return $this->assertStatus(422);
    }
    
    /**
     * Assert response JSON has the given structure
     */
    public function assertJsonStructure(array $structure, ?array $data = null): self
    {
        $data ??= $this->decodedJson;
        
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    // Wildcard array check (e.g., '*' => ['id', 'name'])
                    Assert::assertIsArray($data, "Expected array at path");
                    
                    foreach ($data as $item) {
                        $this->assertJsonStructure($value, $item);
                    }
                } else {
                    // Nested structure check
                    Assert::assertArrayHasKey($key, $data, sprintf("Missing key '%s' in JSON", $key));
                    $this->assertJsonStructure($value, $data[$key]);
                }
            } else {
                // Simple key check
                Assert::assertArrayHasKey($value, $data, sprintf("Missing key '%s' in JSON", $value));
            }
        }
        
        return $this;
    }
    
    /**
     * Assert response JSON contains the given data
     */
    public function assertJson(array $expected): self
    {
        $this->assertJsonContains($expected, $this->decodedJson);
        return $this;
    }
    
    /**
     * Assert response JSON exactly matches the given data
     */
    public function assertExactJson(array $expected): self
    {
        Assert::assertEquals($expected, $this->decodedJson, "JSON does not exactly match expected");
        return $this;
    }
    
    /**
     * Assert response JSON has a specific path with value
     */
    public function assertJsonPath(string $path, mixed $expectedValue): self
    {
        $value = $this->getJsonPath($path);
        Assert::assertEquals($expectedValue, $value, sprintf("JSON path '%s' does not match expected value", $path));
        return $this;
    }
    
    /**
     * Assert response JSON has a specific key
     */
    public function assertJsonHas(string $key): self
    {
        Assert::assertArrayHasKey($key, $this->decodedJson, sprintf("JSON does not have key '%s'", $key));
        return $this;
    }
    
    /**
     * Assert response JSON is missing a specific key
     */
    public function assertJsonMissing(string $key): self
    {
        Assert::assertArrayNotHasKey($key, $this->decodedJson, sprintf("JSON unexpectedly has key '%s'", $key));
        return $this;
    }
    
    /**
     * Assert response JSON array has a specific count
     */
    public function assertJsonCount(int $expectedCount, ?string $key = null): self
    {
        $data = $key ? $this->getJsonPath($key) : $this->decodedJson;
        
        Assert::assertIsArray($data, "JSON data is not an array");
        Assert::assertCount($expectedCount, $data, sprintf('Expected %d items, got ', $expectedCount) . count($data));
        
        return $this;
    }
    
    /**
     * Assert response JSON contains a specific value
     */
    public function assertJsonFragment(array $fragment): self
    {
        $this->assertJsonContains($fragment, $this->decodedJson);
        return $this;
    }
    
    /**
     * Assert response has a specific header
     */
    public function assertHeader(string $headerName, ?string $expectedValue = null): self
    {
        $headers = $this->response->headers;
        
        Assert::assertArrayHasKey($headerName, $headers, sprintf("Header '%s' not found", $headerName));
        
        if ($expectedValue !== null) {
            Assert::assertEquals($expectedValue, $headers[$headerName], sprintf("Header '%s' value does not match", $headerName));
        }
        
        return $this;
    }
    
    /**
     * Assert response header is missing
     */
    public function assertHeaderMissing(string $headerName): self
    {
        $headers = $this->response->headers;
        Assert::assertArrayNotHasKey($headerName, $headers, sprintf("Header '%s' should not be present", $headerName));
        return $this;
    }
    
    /**
     * Get the JSON response data
     */
    public function json(): array
    {
        return $this->decodedJson;
    }
    
    /**
     * Get the raw response
     */
    public function getResponse(): JsonResponse
    {
        return $this->response;
    }
    
    /**
     * Recursively check if expected data is contained in actual data
     */
    private function assertJsonContains(array $expected, array $actual, string $path = ''): void
    {
        foreach ($expected as $key => $value) {
            $currentPath = $path !== '' && $path !== '0' ? sprintf('%s.%s', $path, $key) : $key;
            
            Assert::assertArrayHasKey($key, $actual, sprintf("Missing key '%s' in JSON", $currentPath));
            
            if (is_array($value)) {
                Assert::assertIsArray($actual[$key], sprintf("Expected array at '%s'", $currentPath));
                $this->assertJsonContains($value, $actual[$key], $currentPath);
            } else {
                Assert::assertEquals($value, $actual[$key], sprintf("Value mismatch at '%s'", $currentPath));
            }
        }
    }
    
    /**
     * Get a value from JSON using dot notation
     */
    private function getJsonPath(string $path): mixed
    {
        $segments = explode('.', $path);
        $data = $this->decodedJson;
        
        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                Assert::fail(sprintf("JSON path '%s' not found", $path));
            }

            $data = $data[$segment];
        }
        
        return $data;
    }
}

