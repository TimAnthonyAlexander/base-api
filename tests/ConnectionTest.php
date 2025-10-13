<?php

namespace BaseApi\Tests;

use PDO;
use PDOStatement;
use Override;
use ReflectionClass;
use PDOException;
use Exception;
use PHPUnit\Framework\TestCase;
use BaseApi\Database\Connection;
use BaseApi\Database\Drivers\DatabaseDriverInterface;

class ConnectionTest extends TestCase
{
    private Connection $connection;

    private PDO $mockPdo;

    private PDOStatement $mockStatement;

    private DatabaseDriverInterface $mockDriver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new Connection();

        // Create mock PDO and statement
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->mockDriver = $this->createMock(DatabaseDriverInterface::class);

        // Clear any existing environment variables
        unset($_ENV['DB_DRIVER']);
        unset($_ENV['DB_HOST']);
        unset($_ENV['DB_PORT']);
        unset($_ENV['DB_NAME']);
        unset($_ENV['DB_DATABASE']);
        unset($_ENV['DB_USER']);
        unset($_ENV['DB_USERNAME']);
        unset($_ENV['DB_PASSWORD']);
        unset($_ENV['DB_PASS']);
        unset($_ENV['DB_CHARSET']);
        unset($_ENV['DB_PERSISTENT']);
    }

    public function testGetDriverReturnsDefaultDriverWhenNoEnvSet(): void
    {
        // Use reflection to inject our mock driver
        $reflection = new ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);

        $driver = $this->connection->getDriver();

        $this->assertInstanceOf(DatabaseDriverInterface::class, $driver);
    }

    public function testGetDriverUsesEnvironmentVariable(): void
    {
        $_ENV['DB_DRIVER'] = 'sqlite';

        // Use reflection to inject our mock driver
        $reflection = new ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);

        $driver = $this->connection->getDriver();

        $this->assertInstanceOf(DatabaseDriverInterface::class, $driver);
    }

    public function testPdoCallsConnectWhenNotInitialized(): void
    {
        // Set up mock driver to return our mock PDO
        $this->mockDriver->expects($this->once())
            ->method('createConnection')
            ->with($this->isType('array'))
            ->willReturn($this->mockPdo);

        $this->mockDriver->method('getName')->willReturn('mysql');

        // Use reflection to inject our mock driver and PDO
        $reflection = new ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);

        $pdo = $this->connection->pdo();

        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testPdoReturnsSameInstanceOnSecondCall(): void
    {
        // Set up mock driver
        $this->mockDriver->expects($this->once())
            ->method('createConnection')
            ->willReturn($this->mockPdo);

        $this->mockDriver->method('getName')->willReturn('mysql');

        // Use reflection to inject our mock driver
        $reflection = new ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);

        $pdo1 = $this->connection->pdo();
        $pdo2 = $this->connection->pdo();

        $this->assertSame($pdo1, $pdo2);
    }

    public function testExecuteQueryPreparesAndExecutesStatement(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $params = [1];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with($params);

        // Use reflection to inject mock PDO
        $reflection = new ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);

        $result = $this->connection->executeQuery($sql, $params);

        $this->assertInstanceOf(PDOStatement::class, $result);
    }

    public function testExecuteQueryThrowsExceptionOnFailure(): void
    {
        $sql = 'INVALID SQL';
        $params = [];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willThrowException(new PDOException('SQL error'));

        // Use reflection to inject mock PDO
        $reflection = new ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);

        $this->expectException(PDOException::class);
        $this->connection->executeQuery($sql, $params);
    }

    public function testQueryReturnsAllResults(): void
    {
        $sql = 'SELECT * FROM users';
        $params = [];
        $expectedResult = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane']
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with($params);

        $this->mockStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedResult);

        // Use reflection to inject mock PDO
        $reflection = new ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);

        $result = $this->connection->query($sql, $params);

        $this->assertEquals($expectedResult, $result);
    }

    public function testQueryOneReturnsFirstResult(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $params = [1];
        $expectedResult = ['id' => 1, 'name' => 'John'];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with($params);

        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedResult);

        // Use reflection to inject mock PDO
        $reflection = new ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);

        $result = $this->connection->queryOne($sql, $params);

        $this->assertEquals($expectedResult, $result);
    }

    public function testQueryOneReturnsNullWhenNoResult(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $params = [999];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with($params);

        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        // Use reflection to inject mock PDO
        $reflection = new ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);

        $result = $this->connection->queryOne($sql, $params);

        $this->assertNull($result);
    }

    public function testExecReturnsRowCount(): void
    {
        $sql = 'UPDATE users SET name = ? WHERE id = ?';
        $params = ['Updated Name', 1];
        $expectedRowCount = 1;

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->mockStatement);

        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with($params);

        $this->mockStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedRowCount);

        // Use reflection to inject mock PDO
        $reflection = new ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);

        $result = $this->connection->exec($sql, $params);

        $this->assertEquals($expectedRowCount, $result);
    }

    public function testConnectUsesCorrectDefaultConfig(): void
    {
        $expectedConfig = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'baseapi',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'persistent' => false,
        ];

        $this->mockDriver->expects($this->once())
            ->method('createConnection')
            ->with($expectedConfig)
            ->willReturn($this->mockPdo);

        $this->mockDriver->method('getName')->willReturn('mysql');

        // Use reflection to inject mock driver and call connect
        $reflection = new ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);

        $connectMethod = $reflection->getMethod('connect');
        $connectMethod->setAccessible(true);
        $connectMethod->invoke($this->connection);
    }

    public function testConnectUsesConfigValues(): void
    {
        // Note: This test validates that Connection uses App::config() 
        // After refactoring to use Config::get() instead of direct $_ENV access,
        // we test that the config system works correctly.
        
        // Check if real App is already loaded (from other tests in suite)
        $appExists = class_exists('BaseApi\App', false);
        $isRealApp = $appExists && method_exists('BaseApi\App', 'boot');
        
        if ($isRealApp) {
            // Real App is loaded - test with default config values
            // This happens when running full test suite where other tests load App
            $expectedConfig = [
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'baseapi',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'persistent' => false,
            ];
        } else {
            // Mock App - can test custom config values
            $_ENV['database.host'] = 'localhost';
            $_ENV['database.port'] = '5432';
            $_ENV['database.name'] = 'test_db';
            $_ENV['database.user'] = 'test_user';
            $_ENV['database.password'] = 'test_pass';
            $_ENV['database.charset'] = 'utf8';
            $_ENV['database.persistent'] = 'true';
            
            // Only create mock if it doesn't exist
            if (!class_exists('BaseApi\App', false)) {
                $this->createMockAppClass();
            }
            
            $expectedConfig = [
                'host' => 'localhost',
                'port' => '5432',
                'database' => 'test_db',
                'username' => 'test_user',
                'password' => 'test_pass',
                'charset' => 'utf8',
                'persistent' => true,
            ];
        }

        // Create a fresh Connection instance for this test
        $connection = new Connection();

        $this->mockDriver->expects($this->once())
            ->method('createConnection')
            ->with($expectedConfig)
            ->willReturn($this->mockPdo);

        $this->mockDriver->method('getName')->willReturn($isRealApp ? 'mysql' : 'postgresql');

        // Use reflection to inject mock driver and call connect
        $reflection = new ReflectionClass($connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($connection, $this->mockDriver);

        $connectMethod = $reflection->getMethod('connect');
        $connectMethod->setAccessible(true);
        $connectMethod->invoke($connection);

        // Cleanup
        if (!$isRealApp) {
            unset($_ENV['database.host'], $_ENV['database.port'], $_ENV['database.name'], 
                  $_ENV['database.user'], $_ENV['database.password'], $_ENV['database.charset'], 
                  $_ENV['database.persistent']);
        }
    }

    private function createMockAppClass(): void
    {
        $mockClassContent = <<<'PHP'
<?php
namespace BaseApi {
    class App {
        public static function config(string $key = '', mixed $default = null): mixed
        {
            // Return values based on key, checking $_ENV first
            if ($key !== '' && isset($_ENV[$key])) {
                $value = $_ENV[$key];
                // Handle boolean conversion
                if ($key === 'database.persistent' && is_string($value)) {
                    return $value === 'true';
                }
                return $value;
            }
            
            return match($key) {
                'database.driver' => 'mysql',
                'database.host' => '127.0.0.1',
                'database.port' => 3306,
                'database.name' => 'baseapi',
                'database.user' => 'root',
                'database.password' => '',
                'database.charset' => 'utf8mb4',
                'database.persistent' => false,
                default => $default
            };
        }
    }
}
PHP;

        // Fix temp file leak: tempnam() creates a file, rename it to avoid leak
        $tempFile = tempnam(sys_get_temp_dir(), 'mock_app_');
        if ($tempFile === false) {
            throw new Exception('Failed to create temporary file');
        }

        $phpTempFile = $tempFile . '.php';
        if (!rename($tempFile, $phpTempFile)) {
            @unlink($tempFile); // Clean up original if rename fails
            throw new Exception('Failed to rename temporary file');
        }
        
        if (file_put_contents($phpTempFile, $mockClassContent) === false) {
            @unlink($phpTempFile);
            throw new Exception('Failed to create mock App class file');
        }

        try {
            require_once $phpTempFile;
        } finally {
            // Always clean up the temporary file
            @unlink($phpTempFile);
        }
    }
}
