<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use BaseApi\App;
use BaseApi\Config;
use BaseApi\Logger;
use BaseApi\Router;
use BaseApi\Http\Kernel;
use BaseApi\Database\Connection;
use BaseApi\Database\DB;
use BaseApi\Auth\UserProvider;
use BaseApi\Profiler;
use BaseApi\Container\ContainerInterface;
use BaseApi\Container\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->bind('test.service', fn() => 'test-value');
    }
}

class AppTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset App state before each test
        // Use reflection to reset static properties
        $reflection = new \ReflectionClass(App::class);
        
        $properties = [
            'config', 'logger', 'router', 'kernel', 'connection',
            'db', 'userProvider', 'profiler', 'booted', 'basePath',
            'container', 'serviceProviders'
        ];
        
        foreach ($properties as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            
            if ($propertyName === 'booted') {
                $property->setValue(null, false);
            } elseif ($propertyName === 'serviceProviders') {
                $property->setValue(null, []);
            } else {
                $property->setValue(null, null);
            }
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up after each test
        $this->setUp();
    }
    
    public function testBootInitializesApp()
    {
        // Create temporary config files for testing
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/config', 0755, true);
        
        // Create minimal config
        file_put_contents($tempDir . '/config/app.php', '<?php return ["debug" => true];');
        file_put_contents($tempDir . '/.env', 'APP_ENV=testing');
        
        try {
            App::boot($tempDir);
            
            $this->assertTrue(true); // If we get here, boot succeeded
            
            // Test that services are accessible
            $config = App::config();
            $this->assertInstanceOf(Config::class, $config);
            
            $logger = App::logger();
            $this->assertInstanceOf(Logger::class, $logger);
            
            $router = App::router();
            $this->assertInstanceOf(Router::class, $router);
            
            $kernel = App::kernel();
            $this->assertInstanceOf(Kernel::class, $kernel);
            
            $db = App::db();
            $this->assertInstanceOf(DB::class, $db);
            
            $profiler = App::profiler();
            $this->assertInstanceOf(Profiler::class, $profiler);
            
            $container = App::container();
            $this->assertInstanceOf(ContainerInterface::class, $container);
            
        } finally {
            // Clean up temp directory
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testBootOnlyRunsOnce()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/config', 0755, true);
        file_put_contents($tempDir . '/config/app.php', '<?php return [];');
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            $firstConfig = App::config();
            
            // Boot again
            App::boot($tempDir);
            $secondConfig = App::config();
            
            // Should be the same instance (not re-initialized)
            $this->assertSame($firstConfig, $secondConfig);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testConfigAccess()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/config', 0755, true);
        
        $configData = [
            'app_name' => 'Test App',
            'debug' => true,
            'nested' => [
                'value' => 'test'
            ]
        ];
        
        file_put_contents($tempDir . '/config/app.php', '<?php return ' . var_export($configData, true) . ';');
        file_put_contents($tempDir . '/.env', 'TEST_ENV=testing');
        
        try {
            App::boot($tempDir);
            
            // Test getting entire config
            $config = App::config();
            $this->assertInstanceOf(Config::class, $config);
            
            // Test getting specific config values
            $appName = App::config('app_name');
            $this->assertEquals('Test App', $appName);
            
            $debug = App::config('debug');
            $this->assertTrue($debug);
            
            $nested = App::config('nested.value');
            $this->assertEquals('test', $nested);
            
            // Test default value
            $nonExistent = App::config('non.existent', 'default');
            $this->assertEquals('default', $nonExistent);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testBootsWithoutConfigFile()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            // Should still work with just framework defaults
            $config = App::config();
            $this->assertInstanceOf(Config::class, $config);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testBootsWithoutEnvFile()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/config', 0755, true);
        file_put_contents($tempDir . '/config/app.php', '<?php return [];');
        
        try {
            App::boot($tempDir);
            
            // Should work without .env file
            $config = App::config();
            $this->assertInstanceOf(Config::class, $config);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testRegisterProvider()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            // Register a test provider
            App::registerProvider(TestServiceProvider::class);
            
            // Service should be available
            $container = App::container();
            $testValue = $container->make('test.service');
            $this->assertEquals('test-value', $testValue);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testRegisterProviderInstance()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            // Register a test provider instance
            $provider = new TestServiceProvider();
            App::registerProvider($provider);
            
            // Service should be available
            $container = App::container();
            $testValue = $container->make('test.service');
            $this->assertEquals('test-value', $testValue);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testRegisterProviderBeforeBoot()
    {
        // Register provider before booting
        App::registerProvider(TestServiceProvider::class);
        
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            // Service should still be available after boot
            $container = App::container();
            $testValue = $container->make('test.service');
            $this->assertEquals('test-value', $testValue);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testSetUserProvider()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            $mockUserProvider = $this->createMock(UserProvider::class);
            App::setUserProvider($mockUserProvider);
            
            $retrievedProvider = App::userProvider();
            $this->assertSame($mockUserProvider, $retrievedProvider);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testBasePath()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            $basePath = App::basePath();
            $this->assertEquals($tempDir, $basePath);
            
            $subPath = App::basePath('config/app.php');
            $this->assertEquals($tempDir . '/config/app.php', $subPath);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testStoragePath()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            $storagePath = App::storagePath();
            $this->assertEquals($tempDir . '/storage', $storagePath);
            
            $subPath = App::storagePath('logs/app.log');
            $this->assertEquals($tempDir . '/storage/logs/app.log', $subPath);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testServicesAutoBootOnFirstAccess()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', '');
        
        try {
            // Don't explicitly boot
            
            // But accessing a service should auto-boot
            $config = App::config();
            $this->assertInstanceOf(Config::class, $config);
            
            // Subsequent calls should work too
            $logger = App::logger();
            $this->assertInstanceOf(Logger::class, $logger);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    public function testConfigWithProvidersFromConfigFile()
    {
        $tempDir = sys_get_temp_dir() . '/baseapi_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/config', 0755, true);
        
        $configData = [
            'providers' => [
                TestServiceProvider::class
            ]
        ];
        
        file_put_contents($tempDir . '/config/app.php', '<?php return ' . var_export($configData, true) . ';');
        file_put_contents($tempDir . '/.env', '');
        
        try {
            App::boot($tempDir);
            
            // Provider from config should be registered
            $container = App::container();
            $testValue = $container->make('test.service');
            $this->assertEquals('test-value', $testValue);
            
        } finally {
            $this->recursiveDelete($tempDir);
        }
    }
    
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
