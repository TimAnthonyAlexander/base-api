<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Cache\Stores\FileStore;

class FileStoreTest extends TestCase
{
    private string $tempDir;
    private FileStore $store;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/baseapi-test-' . uniqid();
        $this->store = new FileStore($this->tempDir, 'test', 0755);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->rmdirRecursive($this->tempDir);
        }
    }

    public function testConstructorCreatesDirectory(): void
    {
        $this->assertDirectoryExists($this->tempDir);
        $this->assertTrue(is_writable($this->tempDir));
    }

    public function testConstructorWithNonWritableDirectory(): void
    {
        $readOnlyDir = sys_get_temp_dir() . '/readonly-' . uniqid();
        mkdir($readOnlyDir, 0444);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cache directory is not writable');
        
        new FileStore($readOnlyDir);
        
        rmdir($readOnlyDir);
    }

    public function testPutAndGet(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->store->put($key, $value, null);
        $result = $this->store->get($key);

        $this->assertEquals($value, $result);
    }

    public function testPutAndGetWithTtl(): void
    {
        $key = 'test_key_ttl';
        $value = 'test_value_ttl';

        $this->store->put($key, $value, 1); // 1 second TTL
        $result = $this->store->get($key);
        $this->assertEquals($value, $result);

        // Wait for expiration
        sleep(2);
        $result = $this->store->get($key);
        $this->assertNull($result);
    }

    public function testPutWithExpiredTtl(): void
    {
        $key = 'expired_key';
        $value = 'expired_value';

        // Put with negative TTL (already expired)
        $this->store->put($key, $value, -1);
        $result = $this->store->get($key);
        $this->assertNull($result);
    }

    public function testGetNonExistentKey(): void
    {
        $result = $this->store->get('non_existent_key');
        $this->assertNull($result);
    }

    public function testHas(): void
    {
        $key = 'has_test_key';
        $value = 'has_test_value';

        $this->assertFalse($this->store->has($key));

        $this->store->put($key, $value, null);
        $this->assertTrue($this->store->has($key));
    }

    public function testForget(): void
    {
        $key = 'forget_test_key';
        $value = 'forget_test_value';

        $this->store->put($key, $value, null);
        $this->assertTrue($this->store->has($key));

        $result = $this->store->forget($key);
        $this->assertTrue($result);
        $this->assertFalse($this->store->has($key));
    }

    public function testForgetNonExistentKey(): void
    {
        $result = $this->store->forget('non_existent_key');
        $this->assertFalse($result);
    }

    public function testFlush(): void
    {
        // Put multiple keys
        $this->store->put('key1', 'value1', null);
        $this->store->put('key2', 'value2', null);
        $this->store->put('key3', 'value3', null);

        $this->assertTrue($this->store->has('key1'));
        $this->assertTrue($this->store->has('key2'));
        $this->assertTrue($this->store->has('key3'));

        // Flush all
        $result = $this->store->flush();
        $this->assertTrue($result);

        $this->assertFalse($this->store->has('key1'));
        $this->assertFalse($this->store->has('key2'));
        $this->assertFalse($this->store->has('key3'));
    }

    public function testIncrement(): void
    {
        $key = 'increment_key';

        // Increment non-existent key should start from 0
        $result = $this->store->increment($key, 5);
        $this->assertEquals(5, $result);
        $this->assertEquals(5, $this->store->get($key));

        // Increment existing key
        $result = $this->store->increment($key, 3);
        $this->assertEquals(8, $result);
        $this->assertEquals(8, $this->store->get($key));
    }

    public function testIncrementWithExistingStringValue(): void
    {
        $key = 'increment_string_key';
        
        // Put string value first
        $this->store->put($key, 'not_a_number', null);
        
        // Increment should treat as 0 + value
        $result = $this->store->increment($key, 5);
        $this->assertEquals(5, $result);
    }

    public function testIncrementPreservesTtl(): void
    {
        $key = 'increment_ttl_key';

        // Put with TTL
        $this->store->put($key, 10, 3600); // 1 hour TTL
        
        // Increment should preserve TTL
        $this->store->increment($key, 5);
        
        // Value should be updated but file should still exist
        $this->assertEquals(15, $this->store->get($key));
        
        // Check file exists (not expired)
        $this->assertTrue($this->store->has($key));
    }

    public function testDecrement(): void
    {
        $key = 'decrement_key';

        // Set initial value
        $this->store->put($key, 10, null);

        // Decrement
        $result = $this->store->decrement($key, 3);
        $this->assertEquals(7, $result);
        $this->assertEquals(7, $this->store->get($key));
    }

    public function testCleanup(): void
    {
        // Put some keys with different TTLs
        $this->store->put('active_key', 'active_value', 3600); // 1 hour (not expired)
        $this->store->put('expired_key1', 'expired_value1', -1); // Already expired
        $this->store->put('expired_key2', 'expired_value2', -1); // Already expired
        $this->store->put('permanent_key', 'permanent_value', null); // No expiration

        $removed = $this->store->cleanup();
        
        // Should have removed 2 expired keys
        $this->assertEquals(2, $removed);
        
        // Check remaining keys
        $this->assertTrue($this->store->has('active_key'));
        $this->assertTrue($this->store->has('permanent_key'));
        $this->assertFalse($this->store->has('expired_key1'));
        $this->assertFalse($this->store->has('expired_key2'));
    }

    public function testCleanupCorruptedFiles(): void
    {
        // Create a corrupted cache file manually
        $corruptedFile = $this->tempDir . '/test_corrupted_file';
        file_put_contents($corruptedFile, 'this is not serialized data');

        $removed = $this->store->cleanup();
        $this->assertEquals(1, $removed);
        $this->assertFileDoesNotExist($corruptedFile);
    }

    public function testGetStats(): void
    {
        // Put some test data
        $this->store->put('key1', 'value1', null); // Permanent
        $this->store->put('key2', 'value2', 3600); // Not expired
        $this->store->put('key3', 'value3', -1); // Expired

        $stats = $this->store->getStats();

        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('expired_files', $stats);
        $this->assertArrayHasKey('active_files', $stats);
        $this->assertArrayHasKey('total_size_bytes', $stats);

        $this->assertEquals(3, $stats['total_files']);
        $this->assertEquals(1, $stats['expired_files']);
        $this->assertEquals(2, $stats['active_files']);
        $this->assertGreaterThan(0, $stats['total_size_bytes']);
    }

    public function testGetPrefix(): void
    {
        $this->assertEquals('test', $this->store->getPrefix());
    }

    public function testDifferentDataTypes(): void
    {
        // Test various data types
        $testCases = [
            'string' => 'test_string',
            'integer' => 42,
            'float' => 3.14,
            'boolean_true' => true,
            'boolean_false' => false,
            'array' => ['a', 'b', 'c'],
            'object' => (object) ['prop' => 'value'],
            'null' => null,
        ];

        foreach ($testCases as $key => $value) {
            $this->store->put($key, $value, null);
            $result = $this->store->get($key);
            $this->assertEquals($value, $result, "Failed for data type: $key");
        }
    }

    public function testKeySanitization(): void
    {
        $unsafeKey = 'key/with\\unsafe:*?characters"<>|';
        $value = 'test_value';

        $this->store->put($unsafeKey, $value, null);
        $result = $this->store->get($unsafeKey);

        $this->assertEquals($value, $result);
    }

    public function testAtomicWrites(): void
    {
        $key = 'atomic_test';
        $value = str_repeat('x', 10000); // Large value

        // This should succeed with atomic write
        $this->store->put($key, $value, null);
        $result = $this->store->get($key);

        $this->assertEquals($value, $result);
    }

    public function testGetWithCorruptedFile(): void
    {
        $key = 'corrupted_key';
        
        // First put a valid value
        $this->store->put($key, 'valid_value', null);
        
        // Now corrupt the file
        $filePath = $this->tempDir . '/test_corrupted_key';
        file_put_contents($filePath, 'corrupted data that cannot be unserialized');
        
        // Get should return null and clean up the corrupted file
        $result = $this->store->get($key);
        $this->assertNull($result);
        $this->assertFileDoesNotExist($filePath);
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
