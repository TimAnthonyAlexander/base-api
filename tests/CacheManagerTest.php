<?php

namespace BaseApi\Tests;

use Override;
use ReflectionClass;
use InvalidArgumentException;
use BaseApi\Cache\Stores\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use BaseApi\Cache\CacheManager;
use BaseApi\Cache\CacheInterface;
use BaseApi\Cache\Repository;
use BaseApi\Cache\TaggedCache;
use BaseApi\Cache\Stores\ArrayStore;
use BaseApi\Cache\Stores\FileStore;
use BaseApi\Cache\Stores\RedisStore;
use BaseApi\Config;

class CacheManagerTest extends TestCase
{
    private Config $mockConfig;

    private CacheManager $manager;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = $this->createMock(Config::class);
        $this->manager = new CacheManager($this->mockConfig);
    }

    public function testConstructorSetsConfig(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);

        $this->assertSame($this->mockConfig, $configProperty->getValue($this->manager));
    }

    public function testGetDefaultDriverReturnsConfigValue(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.default', 'array')
            ->willReturn('redis');

        $result = $this->manager->getDefaultDriver();

        $this->assertEquals('redis', $result);
    }

    public function testGetDefaultDriverReturnsArrayAsDefault(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.default', 'array')
            ->willReturn('array');

        $result = $this->manager->getDefaultDriver();

        $this->assertEquals('array', $result);
    }

    public function testGetDefaultDriverCachesResult(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.default', 'array')
            ->willReturn('file');

        $result1 = $this->manager->getDefaultDriver();
        $result2 = $this->manager->getDefaultDriver();

        $this->assertEquals('file', $result1);
        $this->assertEquals('file', $result2);
    }

    public function testSetDefaultDriverOverridesConfigValue(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.default', 'array')
            ->willReturn('array');

        // First call should use config
        $this->assertEquals('array', $this->manager->getDefaultDriver());

        // Set override
        $this->manager->setDefaultDriver('redis');

        // Second call should use override (no more config calls)
        $this->assertEquals('redis', $this->manager->getDefaultDriver());
    }

    public function testExtendRegistersCustomDriver(): void
    {
        $driverName = 'custom';
        $callback = fn($config): ArrayStore => new ArrayStore();

        $this->manager->extend($driverName, $callback);

        $reflection = new ReflectionClass($this->manager);
        $customDriversProperty = $reflection->getProperty('customDrivers');
        $customDriversProperty->setAccessible(true);

        $customDrivers = $customDriversProperty->getValue($this->manager);

        $this->assertArrayHasKey($driverName, $customDrivers);
        $this->assertSame($callback, $customDrivers[$driverName]);
    }

    public function testDriverReturnsDefaultDriverWhenNameIsNull(): void
    {
        $this->mockConfig->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array', 'prefix' => 'test']],
                ['cache.prefix', 'baseapi_cache', 'test_prefix'],
            ]);

        $driver = $this->manager->driver();

        $this->assertInstanceOf(CacheInterface::class, $driver);
    }

    public function testDriverCachesStoreInstances(): void
    {
        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array', 'prefix' => 'test']],
                ['cache.prefix', 'baseapi_cache', 'test_prefix'],
            ]);

        $driver1 = $this->manager->driver('array');
        $driver2 = $this->manager->driver('array');

        $this->assertSame($driver1, $driver2);
    }

    public function testPurgeFlushesAndRemovesStore(): void
    {
        // Set up mocks for creating store
        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array', 'prefix' => 'test']],
                ['cache.prefix', 'baseapi_cache', 'test_prefix'],
            ]);

        // Create a store first
        $store1 = $this->manager->driver('array');

        // Purge it
        $this->manager->purge('array');

        // Get the store again - should be a new instance
        $store2 = $this->manager->driver('array');

        $this->assertNotSame($store1, $store2);
    }

    public function testPurgeUsesDefaultDriverWhenNameIsNull(): void
    {
        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'file'],
                ['cache.stores.file', null, ['driver' => 'file', 'path' => '/tmp/cache']],
                ['cache.prefix', 'baseapi_cache', 'test_prefix'],
            ]);

        // Create store
        $store1 = $this->manager->driver();

        // Purge (should purge default driver)
        $this->manager->purge();

        // Get again
        $store2 = $this->manager->driver();

        $this->assertNotSame($store1, $store2);
    }

    public function testGetStoreNamesReturnsConfiguredStores(): void
    {
        $expectedStores = ['array', 'file', 'redis'];
        $storesConfig = [
            'array' => ['driver' => 'array'],
            'file' => ['driver' => 'file'],
            'redis' => ['driver' => 'redis'],
        ];

        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.stores', [])
            ->willReturn($storesConfig);

        $result = $this->manager->getStoreNames();

        $this->assertEquals($expectedStores, $result);
    }

    public function testGetStoreNamesReturnsEmptyArrayWhenNoStoresConfigured(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.stores', [])
            ->willReturn([]);

        $result = $this->manager->getStoreNames();

        $this->assertEquals([], $result);
    }

    public function testResolveThrowsExceptionForUndefinedStore(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.stores.nonexistent')
            ->willReturn(null);

        $reflection = new ReflectionClass($this->manager);
        $resolveMethod = $reflection->getMethod('resolve');
        $resolveMethod->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache store [nonexistent] is not defined.');

        $resolveMethod->invoke($this->manager, 'nonexistent');
    }

    public function testResolveUsesCustomDriverWhenAvailable(): void
    {
        $customStore = $this->createMock(StoreInterface::class);
        $callback = fn($config): MockObject => $customStore;

        $this->manager->extend('custom', $callback);

        $this->mockConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['cache.stores.custom', null, ['driver' => 'custom', 'config' => 'value']],
                ['cache.prefix', 'baseapi_cache', 'test_prefix'],
            ]);

        $reflection = new ReflectionClass($this->manager);
        $resolveMethod = $reflection->getMethod('resolve');
        $resolveMethod->setAccessible(true);

        $result = $resolveMethod->invoke($this->manager, 'custom');

        $this->assertInstanceOf(Repository::class, $result);
    }

    public function testCreateStoreThrowsExceptionForUnsupportedDriver(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $createStoreMethod = $reflection->getMethod('createStore');
        $createStoreMethod->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache driver [unsupported] is not supported.');

        $createStoreMethod->invoke($this->manager, 'unsupported', []);
    }

    public function testCreateArrayStore(): void
    {
        $config = ['prefix' => 'test_prefix'];

        $reflection = new ReflectionClass($this->manager);
        $createArrayStoreMethod = $reflection->getMethod('createArrayStore');
        $createArrayStoreMethod->setAccessible(true);

        $result = $createArrayStoreMethod->invoke($this->manager, $config);

        $this->assertInstanceOf(ArrayStore::class, $result);
    }

    public function testCreateFileStore(): void
    {
        // Use a valid temporary directory
        $tempDir = sys_get_temp_dir() . '/test_cache_' . uniqid();
        $config = ['path' => $tempDir, 'permissions' => 0644, 'prefix' => 'test_prefix'];

        $reflection = new ReflectionClass($this->manager);
        $createFileStoreMethod = $reflection->getMethod('createFileStore');
        $createFileStoreMethod->setAccessible(true);

        $result = $createFileStoreMethod->invoke($this->manager, $config);

        $this->assertInstanceOf(FileStore::class, $result);

        // Clean up
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }

    public function testCreateFileStoreUsesDefaults(): void
    {
        $config = [];

        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.prefix', 'baseapi_cache')
            ->willReturn('global_prefix');

        $reflection = new ReflectionClass($this->manager);
        $createFileStoreMethod = $reflection->getMethod('createFileStore');
        $createFileStoreMethod->setAccessible(true);

        $result = $createFileStoreMethod->invoke($this->manager, $config);

        $this->assertInstanceOf(FileStore::class, $result);
    }

    public function testCreateRedisStore(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 6380,
            'password' => 'secret',
            'database' => 1,
            'timeout' => 10.0,
            'retry_interval' => 200,
            'read_timeout' => 120.0,
            'prefix' => 'test_prefix'
        ];

        $reflection = new ReflectionClass($this->manager);
        $createRedisStoreMethod = $reflection->getMethod('createRedisStore');
        $createRedisStoreMethod->setAccessible(true);

        $result = $createRedisStoreMethod->invoke($this->manager, $config);

        $this->assertInstanceOf(RedisStore::class, $result);
    }

    public function testCreateRedisStoreUsesEnvironmentDefaults(): void
    {
        $config = [];

        // Set environment variables
        $_ENV['REDIS_HOST'] = 'redis.example.com';
        $_ENV['REDIS_PORT'] = '6380';
        $_ENV['REDIS_PASSWORD'] = 'env_secret';
        $_ENV['REDIS_CACHE_DB'] = '2';

        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.prefix', 'baseapi_cache')
            ->willReturn('global_prefix');

        $reflection = new ReflectionClass($this->manager);
        $createRedisStoreMethod = $reflection->getMethod('createRedisStore');
        $createRedisStoreMethod->setAccessible(true);

        $result = $createRedisStoreMethod->invoke($this->manager, $config);

        $this->assertInstanceOf(RedisStore::class, $result);

        // Clean up environment
        unset($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT'], $_ENV['REDIS_PASSWORD'], $_ENV['REDIS_CACHE_DB']);
    }

    public function testGetStorePrefixReturnsConfigPrefix(): void
    {
        $config = ['prefix' => 'custom_prefix'];

        $reflection = new ReflectionClass($this->manager);
        $getStorePrefixMethod = $reflection->getMethod('getStorePrefix');
        $getStorePrefixMethod->setAccessible(true);

        $result = $getStorePrefixMethod->invoke($this->manager, $config);

        $this->assertEquals('custom_prefix', $result);
    }

    public function testGetStorePrefixFallsBackToGlobalPrefix(): void
    {
        $config = [];

        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.prefix', 'baseapi_cache')
            ->willReturn('global_prefix');

        $reflection = new ReflectionClass($this->manager);
        $getStorePrefixMethod = $reflection->getMethod('getStorePrefix');
        $getStorePrefixMethod->setAccessible(true);

        $result = $getStorePrefixMethod->invoke($this->manager, $config);

        $this->assertEquals('global_prefix', $result);
    }

    public function testGetGlobalPrefixReturnsConfigValue(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('cache.prefix', 'baseapi_cache')
            ->willReturn('custom_global_prefix');

        $reflection = new ReflectionClass($this->manager);
        $getGlobalPrefixMethod = $reflection->getMethod('getGlobalPrefix');
        $getGlobalPrefixMethod->setAccessible(true);

        $result = $getGlobalPrefixMethod->invoke($this->manager);

        $this->assertEquals('custom_global_prefix', $result);
    }

    public function testGetDefaultCachePathReturnsCorrectPath(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $getDefaultCachePathMethod = $reflection->getMethod('getDefaultCachePath');
        $getDefaultCachePathMethod->setAccessible(true);

        $result = $getDefaultCachePathMethod->invoke($this->manager);

        // The method should return the application's storage/cache path
        // We verify it contains 'cache' and is an absolute path
        $this->assertStringContainsString('cache', $result);
        $this->assertStringStartsWith('/', $result);
    }

    // Test CacheInterface delegation methods

    public function testGetDelegatesToDefaultDriver(): void
    {
        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('get')
            ->with('test_key', 'default')
            ->willReturn('cached_value');

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->get('test_key', 'default');

        $this->assertEquals('cached_value', $result);
    }

    public function testPutDelegatesToDefaultDriver(): void
    {
        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('put')
            ->with('test_key', 'test_value', 300)
            ->willReturn(true);

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->put('test_key', 'test_value', 300);

        $this->assertTrue($result);
    }

    public function testForgetDelegatesToDefaultDriver(): void
    {
        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('forget')
            ->with('test_key')
            ->willReturn(true);

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->forget('test_key');

        $this->assertTrue($result);
    }

    public function testFlushDelegatesToDefaultDriver(): void
    {
        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('flush')
            ->willReturn(true);

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->flush();

        $this->assertTrue($result);
    }

    public function testRememberDelegatesToDefaultDriver(): void
    {
        $callback = fn(): string => 'computed_value';

        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('remember')
            ->with('test_key', 300, $callback)
            ->willReturn('computed_value');

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->remember('test_key', 300, $callback);

        $this->assertEquals('computed_value', $result);
    }

    public function testForeverDelegatesToDefaultDriver(): void
    {
        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('forever')
            ->with('test_key', 'permanent_value')
            ->willReturn(true);

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->forever('test_key', 'permanent_value');

        $this->assertTrue($result);
    }

    public function testIncrementDelegatesToDefaultDriver(): void
    {
        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('increment')
            ->with('counter', 5)
            ->willReturn(10);

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->increment('counter', 5);

        $this->assertEquals(10, $result);
    }

    public function testDecrementDelegatesToDefaultDriver(): void
    {
        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('decrement')
            ->with('counter', 3)
            ->willReturn(2);

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->decrement('counter', 3);

        $this->assertEquals(2, $result);
    }

    public function testTagsDelegatesToDefaultDriver(): void
    {
        $tags = ['tag1', 'tag2'];
        $mockTaggedCache = $this->createMock(TaggedCache::class);

        $mockRepository = $this->createMock(CacheInterface::class);
        $mockRepository->expects($this->once())
            ->method('tags')
            ->with($tags)
            ->willReturn($mockTaggedCache);

        $this->mockConfig->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', 'array', 'array'],
                ['cache.stores.array', null, ['driver' => 'array']],
                ['cache.prefix', 'baseapi_cache', 'prefix'],
            ]);

        // Use reflection to inject mock repository
        $reflection = new ReflectionClass($this->manager);
        $storesProperty = $reflection->getProperty('stores');
        $storesProperty->setAccessible(true);
        $storesProperty->setValue($this->manager, ['array' => $mockRepository]);

        $result = $this->manager->tags($tags);

        $this->assertSame($mockTaggedCache, $result);
    }
}
