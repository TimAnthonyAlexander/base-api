<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Cache\Cache;
use BaseApi\Cache\CacheManager;
use BaseApi\Cache\CacheInterface;
use BaseApi\Cache\TaggedCache;
use BaseApi\App;
use BaseApi\Container\ContainerInterface;

class CacheTest extends TestCase
{
    private CacheManager $mockManager;
    private CacheInterface $mockDriver;
    private ContainerInterface $mockContainer;
    private TaggedCache $mockTaggedCache;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockManager = $this->createMock(CacheManager::class);
        $this->mockDriver = $this->createMock(CacheInterface::class);
        $this->mockContainer = $this->createMock(ContainerInterface::class);
        $this->mockTaggedCache = $this->createMock(TaggedCache::class);
        
        // Reset the Cache static state
        Cache::reset();
        
        // Mock App::container() to return our mock container
        $this->mockContainer->expects($this->any())
            ->method('make')
            ->with(CacheManager::class)
            ->willReturn($this->mockManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Cache::reset();
    }

    public function testManagerReturnsSameInstanceOnMultipleCalls(): void
    {
        // Use reflection to inject mock manager directly to avoid App dependency
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $manager1 = Cache::manager();
        $manager2 = Cache::manager();
        
        $this->assertSame($manager1, $manager2);
    }

    public function testDriverDelegatesToManager(): void
    {
        $driverName = 'redis';
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->with($driverName)
            ->willReturn($this->mockDriver);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $driver = Cache::driver($driverName);
        
        $this->assertSame($this->mockDriver, $driver);
    }

    public function testGetDelegatesToManager(): void
    {
        $key = 'test_key';
        $default = 'default_value';
        $expected = 'cached_value';
        
        $this->mockManager->expects($this->once())
            ->method('get')
            ->with($key, $default)
            ->willReturn($expected);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::get($key, $default);
        
        $this->assertEquals($expected, $result);
    }

    public function testPutDelegatesToManager(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        $ttl = 300;
        
        $this->mockManager->expects($this->once())
            ->method('put')
            ->with($key, $value, $ttl)
            ->willReturn(true);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::put($key, $value, $ttl);
        
        $this->assertTrue($result);
    }

    public function testForgetDelegatesToManager(): void
    {
        $key = 'test_key';
        
        $this->mockManager->expects($this->once())
            ->method('forget')
            ->with($key)
            ->willReturn(true);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::forget($key);
        
        $this->assertTrue($result);
    }

    public function testFlushDelegatesToManager(): void
    {
        $this->mockManager->expects($this->once())
            ->method('flush')
            ->willReturn(true);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::flush();
        
        $this->assertTrue($result);
    }

    public function testRememberDelegatesToManager(): void
    {
        $key = 'test_key';
        $ttl = 300;
        $callback = fn() => 'computed_value';
        $expected = 'computed_value';
        
        $this->mockManager->expects($this->once())
            ->method('remember')
            ->with($key, $ttl, $callback)
            ->willReturn($expected);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::remember($key, $ttl, $callback);
        
        $this->assertEquals($expected, $result);
    }

    public function testForeverDelegatesToManager(): void
    {
        $key = 'test_key';
        $value = 'permanent_value';
        
        $this->mockManager->expects($this->once())
            ->method('forever')
            ->with($key, $value)
            ->willReturn(true);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::forever($key, $value);
        
        $this->assertTrue($result);
    }

    public function testIncrementDelegatesToManager(): void
    {
        $key = 'counter';
        $value = 5;
        $expected = 10;
        
        $this->mockManager->expects($this->once())
            ->method('increment')
            ->with($key, $value)
            ->willReturn($expected);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::increment($key, $value);
        
        $this->assertEquals($expected, $result);
    }

    public function testDecrementDelegatesToManager(): void
    {
        $key = 'counter';
        $value = 3;
        $expected = 2;
        
        $this->mockManager->expects($this->once())
            ->method('decrement')
            ->with($key, $value)
            ->willReturn($expected);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::decrement($key, $value);
        
        $this->assertEquals($expected, $result);
    }

    public function testTagsDelegatesToManager(): void
    {
        $tags = ['tag1', 'tag2'];
        
        $this->mockManager->expects($this->once())
            ->method('tags')
            ->with($tags)
            ->willReturn($this->mockTaggedCache);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::tags($tags);
        
        $this->assertSame($this->mockTaggedCache, $result);
    }

    public function testExtendDelegatesToManager(): void
    {
        $driver = 'custom';
        $callback = fn() => 'custom_driver';
        
        $this->mockManager->expects($this->once())
            ->method('extend')
            ->with($driver, $callback);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        Cache::extend($driver, $callback);
    }

    public function testPurgeDelegatesToManager(): void
    {
        $storeName = 'redis';
        
        $this->mockManager->expects($this->once())
            ->method('purge')
            ->with($storeName);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        Cache::purge($storeName);
    }

    public function testHasUsesDriverHasMethodWhenAvailable(): void
    {
        $key = 'test_key';
        
        // Create a mock object that implements both CacheInterface and has the 'has' method
        $mockDriverWithHas = new class implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed { return null; }
            public function put(string $key, mixed $value, ?int $ttl = null): bool { return true; }
            public function forget(string $key): bool { return true; }
            public function flush(): bool { return true; }
            public function remember(string $key, int $ttl, callable $callback): mixed { return null; }
            public function forever(string $key, mixed $value): bool { return true; }
            public function increment(string $key, int $value = 1): int { return 1; }
            public function decrement(string $key, int $value = 1): int { return 1; }
            public function tags(array $tags): TaggedCache { return new TaggedCache(new \BaseApi\Cache\Stores\ArrayStore(), $tags); }
            
            public function has(string $key): bool { return true; }
        };
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithHas);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::has($key);
        
        $this->assertTrue($result);
    }

    public function testHasFallsBackToGetWhenDriverLacksHasMethod(): void
    {
        $key = 'test_key';
        $expectedValue = 'some_value';
        
        // Create a driver that implements CacheInterface but doesn't have the 'has' method
        // Since CacheInterface doesn't include 'has', method_exists() should return false
        $mockDriverWithoutHas = $this->createMock(CacheInterface::class);
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithoutHas);
        
        $this->mockManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($expectedValue);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::has($key);
        
        $this->assertTrue($result);
    }

    public function testHasFallsBackToGetReturningFalseForNullValue(): void
    {
        $key = 'test_key';
        
        // Create a driver that implements CacheInterface but doesn't have the 'has' method
        $mockDriverWithoutHas = $this->createMock(CacheInterface::class);
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithoutHas);
        
        $this->mockManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn(null);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::has($key);
        
        $this->assertFalse($result);
    }

    public function testManyUsesDriverManyMethodWhenAvailable(): void
    {
        $keys = ['key1', 'key2'];
        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        
        // Create a mock object that implements CacheInterface and has the 'many' method
        $mockDriverWithMany = new class implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed { return null; }
            public function put(string $key, mixed $value, ?int $ttl = null): bool { return true; }
            public function forget(string $key): bool { return true; }
            public function flush(): bool { return true; }
            public function remember(string $key, int $ttl, callable $callback): mixed { return null; }
            public function forever(string $key, mixed $value): bool { return true; }
            public function increment(string $key, int $value = 1): int { return 1; }
            public function decrement(string $key, int $value = 1): int { return 1; }
            public function tags(array $tags): TaggedCache { return new TaggedCache(new \BaseApi\Cache\Stores\ArrayStore(), $tags); }
            
            public function many(array $keys): array { return ['key1' => 'value1', 'key2' => 'value2']; }
        };
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithMany);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::many($keys);
        
        $this->assertEquals($expected, $result);
    }

    public function testManyFallsBackToIndividualGetsWhenDriverLacksManyMethod(): void
    {
        $keys = ['key1', 'key2'];
        
        $mockDriverWithoutMany = $this->createMock(CacheInterface::class);
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithoutMany);
        
        $this->mockManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['key1', null, 'value1'],
                ['key2', null, 'value2']
            ]);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::many($keys);
        
        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertEquals($expected, $result);
    }

    public function testPutManyFallsBackToIndividualPutsWhenDriverLacksPutManyMethod(): void
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];
        $ttl = 300;
        
        $mockDriverWithoutPutMany = $this->createMock(CacheInterface::class);
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithoutPutMany);
        
        $this->mockManager->expects($this->exactly(2))
            ->method('put')
            ->willReturnMap([
                ['key1', 'value1', $ttl, true],
                ['key2', 'value2', $ttl, true]
            ]);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::putMany($values, $ttl);
        
        $this->assertTrue($result);
    }

    public function testAddReturnsFalseWhenKeyExists(): void
    {
        $key = 'existing_key';
        $value = 'new_value';
        $ttl = 300;
        
        // Create a driver with has method that returns true
        $mockDriverWithHas = new class implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed { return 'existing_value'; }
            public function put(string $key, mixed $value, ?int $ttl = null): bool { return true; }
            public function forget(string $key): bool { return true; }
            public function flush(): bool { return true; }
            public function remember(string $key, int $ttl, callable $callback): mixed { return null; }
            public function forever(string $key, mixed $value): bool { return true; }
            public function increment(string $key, int $value = 1): int { return 1; }
            public function decrement(string $key, int $value = 1): int { return 1; }
            public function tags(array $tags): TaggedCache { return new TaggedCache(new \BaseApi\Cache\Stores\ArrayStore(), $tags); }
            
            public function has(string $key): bool { return true; }
        };
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithHas);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::add($key, $value, $ttl);
        
        $this->assertFalse($result);
    }

    public function testAddStoresValueWhenKeyDoesNotExist(): void
    {
        $key = 'new_key';
        $value = 'new_value';
        $ttl = 300;
        
        // Create a driver with has method that returns false
        $mockDriverWithHas = new class implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed { return null; }
            public function put(string $key, mixed $value, ?int $ttl = null): bool { return true; }
            public function forget(string $key): bool { return true; }
            public function flush(): bool { return true; }
            public function remember(string $key, int $ttl, callable $callback): mixed { return null; }
            public function forever(string $key, mixed $value): bool { return true; }
            public function increment(string $key, int $value = 1): int { return 1; }
            public function decrement(string $key, int $value = 1): int { return 1; }
            public function tags(array $tags): TaggedCache { return new TaggedCache(new \BaseApi\Cache\Stores\ArrayStore(), $tags); }
            
            public function has(string $key): bool { return false; }
        };
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->willReturn($mockDriverWithHas);
        
        $this->mockManager->expects($this->once())
            ->method('put')
            ->with($key, $value, $ttl)
            ->willReturn(true);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::add($key, $value, $ttl);
        
        $this->assertTrue($result);
    }

    public function testPullGetsValueAndRemovesKey(): void
    {
        $key = 'test_key';
        $default = 'default_value';
        $expectedValue = 'cached_value';
        
        $this->mockManager->expects($this->once())
            ->method('get')
            ->with($key, $default)
            ->willReturn($expectedValue);
        
        $this->mockManager->expects($this->once())
            ->method('forget')
            ->with($key);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::pull($key, $default);
        
        $this->assertEquals($expectedValue, $result);
    }

    public function testStatsReturnsEmptyArrayWhenDriverLacksGetStatsMethod(): void
    {
        $mockDriverWithoutStats = $this->createMock(CacheInterface::class);
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->with(null)
            ->willReturn($mockDriverWithoutStats);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::stats();
        
        $this->assertEquals([], $result);
    }

    public function testCleanupReturnsZeroWhenDriverLacksCleanupMethod(): void
    {
        $mockDriverWithoutCleanup = $this->createMock(CacheInterface::class);
        
        $this->mockManager->expects($this->once())
            ->method('driver')
            ->with(null)
            ->willReturn($mockDriverWithoutCleanup);
        
        // Use reflection to inject mock manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        $result = Cache::cleanup();
        
        $this->assertEquals(0, $result);
    }

    public function testKeyGeneratesCorrectKeyFromComponents(): void
    {
        $result = Cache::key('users', 123, 'profile');
        $this->assertEquals('users:123:profile', $result);
        
        $result = Cache::key('single');
        $this->assertEquals('single', $result);
    }

    public function testKeyHandlesArrayComponents(): void
    {
        $arrayComponent = ['sort' => 'name', 'filter' => 'active'];
        $expectedHash = md5(serialize($arrayComponent));
        
        $result = Cache::key('users', $arrayComponent, 'list');
        $expected = "users:{$expectedHash}:list";
        
        $this->assertEquals($expected, $result);
    }

    public function testKeyConvertsNonStringComponentsToStrings(): void
    {
        $result = Cache::key('item', 42, true, 3.14);
        $this->assertEquals('item:42:1:3.14', $result);
    }

    public function testResetClearsManagerInstance(): void
    {
        // Use reflection to set a manager
        $reflection = new \ReflectionClass(Cache::class);
        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue(null, $this->mockManager);
        
        // Verify manager is set
        $this->assertSame($this->mockManager, Cache::manager());
        
        // Reset and verify it's cleared
        Cache::reset();
        $managerValue = $managerProperty->getValue(null);
        $this->assertNull($managerValue);
    }
}
