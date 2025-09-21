<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Cache\Stores\ArrayStore;
use BaseApi\Time\FrozenClock;

class ArrayStoreTest extends TestCase
{
    private ArrayStore $store;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new FrozenClock();
        $this->store = new ArrayStore('', $this->clock);
    }

    public function testConstructorSetsPrefix(): void
    {
        $store = new ArrayStore('test_prefix', $this->clock);
        $this->assertEquals('test_prefix', $store->getPrefix());
    }

    public function testConstructorWithEmptyPrefix(): void
    {
        $store = new ArrayStore('', $this->clock);
        $this->assertEquals('', $store->getPrefix());
    }

    public function testConstructorWithoutPrefix(): void
    {
        $store = new ArrayStore('', $this->clock);
        $this->assertEquals('', $store->getPrefix());
    }

    public function testPutAndGetValue(): void
    {
        $this->store->put('test_key', 'test_value', null);
        $result = $this->store->get('test_key');
        
        $this->assertEquals('test_value', $result);
    }

    public function testPutAndGetWithDifferentDataTypes(): void
    {
        $testCases = [
            ['string_key', 'string_value'],
            ['int_key', 42],
            ['float_key', 3.14],
            ['bool_key', true],
            ['array_key', ['nested', 'array']],
            ['null_key', null],
            ['object_key', (object)['prop' => 'value']],
        ];

        foreach ($testCases as [$key, $value]) {
            $this->store->put($key, $value, null);
            $result = $this->store->get($key);
            
            $this->assertEquals($value, $result, "Failed for key: {$key}");
        }
    }

    public function testGetNonExistentKeyReturnsNull(): void
    {
        $result = $this->store->get('nonexistent');
        $this->assertNull($result);
    }

    public function testPutWithTtl(): void
    {
        $this->store->put('expiring_key', 'expiring_value', 1);
        
        // Should exist immediately
        $this->assertEquals('expiring_value', $this->store->get('expiring_key'));
        
        // Advance clock past expiration
        $this->clock->advance(2);
        
        // Should be expired now
        $this->assertNull($this->store->get('expiring_key'));
    }

    public function testPutWithZeroTtlCreatesExpiredItem(): void
    {
        $this->store->put('zero_ttl', 'value', 0);
        
        // With TTL of 0, the item expires at current time
        // Advance clock briefly to ensure we're past the expiration time
        $this->clock->advance(1);
        $this->assertNull($this->store->get('zero_ttl'));
    }

    public function testPutWithNullTtlNeverExpires(): void
    {
        $this->store->put('permanent', 'permanent_value', null);
        
        $result = $this->store->get('permanent');
        $this->assertEquals('permanent_value', $result);
    }

    public function testForgetExistingKey(): void
    {
        $this->store->put('key_to_forget', 'value', null);
        $this->assertEquals('value', $this->store->get('key_to_forget'));
        
        $result = $this->store->forget('key_to_forget');
        
        $this->assertTrue($result);
        $this->assertNull($this->store->get('key_to_forget'));
    }

    public function testForgetNonExistentKey(): void
    {
        $result = $this->store->forget('nonexistent');
        
        $this->assertFalse($result);
    }

    public function testFlushClearsAllData(): void
    {
        $this->store->put('key1', 'value1', null);
        $this->store->put('key2', 'value2', null);
        $this->store->put('key3', 'value3', null);
        
        // Verify data exists
        $this->assertEquals('value1', $this->store->get('key1'));
        $this->assertEquals('value2', $this->store->get('key2'));
        $this->assertEquals('value3', $this->store->get('key3'));
        
        $result = $this->store->flush();
        
        $this->assertTrue($result);
        $this->assertNull($this->store->get('key1'));
        $this->assertNull($this->store->get('key2'));
        $this->assertNull($this->store->get('key3'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->store->put('existing_key', 'value', null);
        
        $this->assertTrue($this->store->has('existing_key'));
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        $this->assertFalse($this->store->has('nonexistent_key'));
    }

    public function testHasReturnsFalseForExpiredKey(): void
    {
        $this->store->put('expiring_key', 'value', 1);
        
        // Should exist initially
        $this->assertTrue($this->store->has('expiring_key'));
        
        // Advance clock past expiration
        $this->clock->advance(2);
        
        // Should be expired
        $this->assertFalse($this->store->has('expiring_key'));
    }

    public function testIncrementWithExistingNumericValue(): void
    {
        $this->store->put('counter', 5, null);
        
        $result = $this->store->increment('counter', 3);
        
        $this->assertEquals(8, $result);
        $this->assertEquals(8, $this->store->get('counter'));
    }

    public function testIncrementWithNonExistentKey(): void
    {
        $result = $this->store->increment('new_counter', 10);
        
        $this->assertEquals(10, $result);
        $this->assertEquals(10, $this->store->get('new_counter'));
    }

    public function testIncrementWithNonNumericValue(): void
    {
        $this->store->put('non_numeric', 'string_value', null);
        
        $result = $this->store->increment('non_numeric', 5);
        
        $this->assertEquals(5, $result);
        $this->assertEquals(5, $this->store->get('non_numeric'));
    }

    public function testIncrementPreservesTtl(): void
    {
        $this->store->put('counter_with_ttl', 10, 3600); // 1 hour TTL
        
        $result = $this->store->increment('counter_with_ttl', 5);
        
        $this->assertEquals(15, $result);
        $this->assertEquals(15, $this->store->get('counter_with_ttl'));
        
        // Check that TTL was preserved by accessing storage directly
        $reflection = new \ReflectionClass($this->store);
        $storageProperty = $reflection->getProperty('storage');
        $storageProperty->setAccessible(true);
        $storage = $storageProperty->getValue($this->store);
        
        $prefixedKeyMethod = $reflection->getMethod('prefixedKey');
        $prefixedKeyMethod->setAccessible(true);
        $prefixedKey = $prefixedKeyMethod->invoke($this->store, 'counter_with_ttl');
        
        $this->assertNotNull($storage[$prefixedKey]['expires_at']);
        $this->assertGreaterThan($this->clock->now() + 3500, $storage[$prefixedKey]['expires_at']); // Should be close to original TTL
    }

    public function testIncrementWithExpiredKey(): void
    {
        $this->store->put('expired_counter', 5, 1);
        
        // Advance clock past expiration
        $this->clock->advance(2);
        
        // Should treat expired key as non-existent
        $result = $this->store->increment('expired_counter', 10);
        
        $this->assertEquals(10, $result);
        $this->assertEquals(10, $this->store->get('expired_counter'));
    }

    public function testDecrementWithExistingValue(): void
    {
        $this->store->put('counter', 10, null);
        
        $result = $this->store->decrement('counter', 3);
        
        $this->assertEquals(7, $result);
        $this->assertEquals(7, $this->store->get('counter'));
    }

    public function testDecrementWithNonExistentKey(): void
    {
        $result = $this->store->decrement('new_counter', 5);
        
        $this->assertEquals(-5, $result);
        $this->assertEquals(-5, $this->store->get('new_counter'));
    }

    public function testIncrementWithDefaultValue(): void
    {
        $result = $this->store->increment('default_counter', 1);
        
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $this->store->get('default_counter'));
    }

    public function testDecrementWithDefaultValue(): void
    {
        $result = $this->store->decrement('default_counter', 1);
        
        $this->assertEquals(-1, $result);
        $this->assertEquals(-1, $this->store->get('default_counter'));
    }

    public function testGetStatsWithEmptyStore(): void
    {
        $stats = $this->store->getStats();
        
        $expectedStats = [
            'total_items' => 0,
            'expired_items' => 0,
            'active_items' => 0,
            'estimated_memory_bytes' => 0,
        ];
        
        $this->assertEquals($expectedStats, $stats);
    }

    public function testGetStatsWithActiveItems(): void
    {
        $this->store->put('active1', 'value1', null);
        $this->store->put('active2', 'value2', 3600);
        
        $stats = $this->store->getStats();
        
        $this->assertEquals(2, $stats['total_items']);
        $this->assertEquals(0, $stats['expired_items']);
        $this->assertEquals(2, $stats['active_items']);
        $this->assertGreaterThan(0, $stats['estimated_memory_bytes']);
    }

    public function testGetStatsWithExpiredItems(): void
    {
        $this->store->put('active', 'value', 3600);
        $this->store->put('expired1', 'value', 1);
        $this->store->put('expired2', 'value', 1);
        
        // Advance clock past expiration
        $this->clock->advance(2);
        
        $stats = $this->store->getStats();
        
        $this->assertEquals(3, $stats['total_items']);
        $this->assertEquals(2, $stats['expired_items']);
        $this->assertEquals(1, $stats['active_items']);
        $this->assertGreaterThan(0, $stats['estimated_memory_bytes']);
    }

    public function testCleanupWithNoExpiredItems(): void
    {
        $this->store->put('active1', 'value1', null);
        $this->store->put('active2', 'value2', 3600);
        
        $removed = $this->store->cleanup();
        
        $this->assertEquals(0, $removed);
        $this->assertEquals('value1', $this->store->get('active1'));
        $this->assertEquals('value2', $this->store->get('active2'));
    }

    public function testCleanupWithExpiredItems(): void
    {
        $this->store->put('active', 'active_value', 3600);
        $this->store->put('expired1', 'expired_value1', 1);
        $this->store->put('expired2', 'expired_value2', 1);
        
        // Advance clock past expiration
        $this->clock->advance(2);
        
        $removed = $this->store->cleanup();
        
        $this->assertEquals(2, $removed);
        $this->assertEquals('active_value', $this->store->get('active'));
        $this->assertNull($this->store->get('expired1'));
        $this->assertNull($this->store->get('expired2'));
    }

    public function testCleanupOnEmptyStore(): void
    {
        $removed = $this->store->cleanup();
        
        $this->assertEquals(0, $removed);
    }

    public function testPrefixedKeyWithPrefix(): void
    {
        $store = new ArrayStore('test_prefix', $this->clock);
        $store->put('key', 'value', null);
        
        // Verify the key is stored with prefix
        $reflection = new \ReflectionClass($store);
        $storageProperty = $reflection->getProperty('storage');
        $storageProperty->setAccessible(true);
        $storage = $storageProperty->getValue($store);
        
        $this->assertArrayHasKey('test_prefix:key', $storage);
        $this->assertEquals('value', $store->get('key'));
    }

    public function testPrefixedKeyWithoutPrefix(): void
    {
        $this->store->put('key', 'value', null);
        
        // Verify the key is stored without prefix
        $reflection = new \ReflectionClass($this->store);
        $storageProperty = $reflection->getProperty('storage');
        $storageProperty->setAccessible(true);
        $storage = $storageProperty->getValue($this->store);
        
        $this->assertArrayHasKey('key', $storage);
        $this->assertEquals('value', $this->store->get('key'));
    }

    public function testMultipleStoresWithDifferentPrefixes(): void
    {
        $store1 = new ArrayStore('prefix1', $this->clock);
        $store2 = new ArrayStore('prefix2', $this->clock);
        
        $store1->put('same_key', 'value1', null);
        $store2->put('same_key', 'value2', null);
        
        $this->assertEquals('value1', $store1->get('same_key'));
        $this->assertEquals('value2', $store2->get('same_key'));
    }

    public function testGetExpiredItemRemovesFromStorage(): void
    {
        $this->store->put('expiring', 'value', 1);
        
        // Verify it's stored
        $this->assertEquals('value', $this->store->get('expiring'));
        
        // Advance clock past expiration
        $this->clock->advance(2);
        
        // Access expired item (should remove it)
        $this->assertNull($this->store->get('expiring'));
        
        // Verify it's actually removed from storage
        $reflection = new \ReflectionClass($this->store);
        $storageProperty = $reflection->getProperty('storage');
        $storageProperty->setAccessible(true);
        $storage = $storageProperty->getValue($this->store);
        
        $this->assertArrayNotHasKey('expiring', $storage);
    }

    public function testOverwriteExistingKey(): void
    {
        $this->store->put('key', 'original_value', null);
        $this->assertEquals('original_value', $this->store->get('key'));
        
        $this->store->put('key', 'new_value', null);
        $this->assertEquals('new_value', $this->store->get('key'));
    }

    public function testOverwriteWithDifferentTtl(): void
    {
        $this->store->put('key', 'value1', 3600);
        $this->assertEquals('value1', $this->store->get('key'));
        
        $this->store->put('key', 'value2', 1);
        $this->assertEquals('value2', $this->store->get('key'));
        
        // Advance clock past new TTL expiration
        $this->clock->advance(2);
        $this->assertNull($this->store->get('key'));
    }

    public function testLargeDataStorage(): void
    {
        $largeData = str_repeat('a', 10000); // 10KB string
        $this->store->put('large_key', $largeData, null);
        
        $result = $this->store->get('large_key');
        $this->assertEquals($largeData, $result);
    }

    public function testComplexObjectStorage(): void
    {
        $complexObject = (object)[
            'array' => [1, 2, 3, ['nested' => true]],
            'string' => 'test',
            'number' => 42,
            'boolean' => false,
            'null_value' => null,
        ];
        
        $this->store->put('complex', $complexObject, null);
        $result = $this->store->get('complex');
        
        $this->assertEquals($complexObject, $result);
    }

    public function testIncrementWithStringNumericValue(): void
    {
        $this->store->put('string_number', '15', null);
        
        $result = $this->store->increment('string_number', 5);
        
        $this->assertEquals(20, $result);
        $this->assertEquals(20, $this->store->get('string_number'));
    }

    public function testIncrementWithFloatValue(): void
    {
        $this->store->put('float_number', 3.5, null);
        
        $result = $this->store->increment('float_number', 2);
        
        $this->assertEquals(5, $result); // Should convert to int
        $this->assertEquals(5, $this->store->get('float_number'));
    }
}
