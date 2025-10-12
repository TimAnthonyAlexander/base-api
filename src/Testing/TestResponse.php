<?php

namespace BaseApi\Testing;

use BaseApi\Http\JsonResponse;
use PHPUnit\Framework\Assert;

/**
 * Fluent test response for easy assertions
 */
class TestResponse
{
    private JsonResponse $response;
    private array $decodedJson;
    
    public function __construct(JsonResponse $response)
    {
        $this->response = $response;
        $this->decodedJson = $response->getData();
    }
    
    /**
     * Assert response has the given status code
     */
    public function assertStatus(int $expectedStatus): self
    {
        Assert::assertEquals(
            $expectedStatus,
            $this->response->getStatusCode(),
            "Expected status code {$expectedStatus}, got {$this->response->getStatusCode()}"
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
        $data = $data ?? $this->decodedJson;
        
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
                    Assert::assertArrayHasKey($key, $data, "Missing key '{$key}' in JSON");
                    $this->assertJsonStructure($value, $data[$key]);
                }
            } else {
                // Simple key check
                Assert::assertArrayHasKey($value, $data, "Missing key '{$value}' in JSON");
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
        Assert::assertEquals($expectedValue, $value, "JSON path '{$path}' does not match expected value");
        return $this;
    }
    
    /**
     * Assert response JSON has a specific key
     */
    public function assertJsonHas(string $key): self
    {
        Assert::assertArrayHasKey($key, $this->decodedJson, "JSON does not have key '{$key}'");
        return $this;
    }
    
    /**
     * Assert response JSON is missing a specific key
     */
    public function assertJsonMissing(string $key): self
    {
        Assert::assertArrayNotHasKey($key, $this->decodedJson, "JSON unexpectedly has key '{$key}'");
        return $this;
    }
    
    /**
     * Assert response JSON array has a specific count
     */
    public function assertJsonCount(int $expectedCount, ?string $key = null): self
    {
        $data = $key ? $this->getJsonPath($key) : $this->decodedJson;
        
        Assert::assertIsArray($data, "JSON data is not an array");
        Assert::assertCount($expectedCount, $data, "Expected {$expectedCount} items, got " . count($data));
        
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
        $headers = $this->response->getHeaders();
        
        Assert::assertArrayHasKey($headerName, $headers, "Header '{$headerName}' not found");
        
        if ($expectedValue !== null) {
            Assert::assertEquals($expectedValue, $headers[$headerName], "Header '{$headerName}' value does not match");
        }
        
        return $this;
    }
    
    /**
     * Assert response header is missing
     */
    public function assertHeaderMissing(string $headerName): self
    {
        $headers = $this->response->getHeaders();
        Assert::assertArrayNotHasKey($headerName, $headers, "Header '{$headerName}' should not be present");
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
     * Dump the JSON response and continue
     */
    public function dump(): self
    {
        var_dump($this->decodedJson);
        return $this;
    }
    
    /**
     * Dump the JSON response and die
     */
    public function dd(): void
    {
        var_dump($this->decodedJson);
        die(1);
    }
    
    /**
     * Recursively check if expected data is contained in actual data
     */
    private function assertJsonContains(array $expected, array $actual, string $path = ''): void
    {
        foreach ($expected as $key => $value) {
            $currentPath = $path ? "{$path}.{$key}" : $key;
            
            Assert::assertArrayHasKey($key, $actual, "Missing key '{$currentPath}' in JSON");
            
            if (is_array($value)) {
                Assert::assertIsArray($actual[$key], "Expected array at '{$currentPath}'");
                $this->assertJsonContains($value, $actual[$key], $currentPath);
            } else {
                Assert::assertEquals($value, $actual[$key], "Value mismatch at '{$currentPath}'");
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
                Assert::fail("JSON path '{$path}' not found");
            }
            $data = $data[$segment];
        }
        
        return $data;
    }
}

