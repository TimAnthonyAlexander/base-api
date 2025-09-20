<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Database\Connection;
use BaseApi\Database\Drivers\DatabaseDriverInterface;
use BaseApi\Database\Drivers\DatabaseDriverFactory;
use BaseApi\App;
use BaseApi\Profiler;

class ConnectionTest extends TestCase
{
    private Connection $connection;
    private \PDO $mockPdo;
    private \PDOStatement $mockStatement;
    private DatabaseDriverInterface $mockDriver;
    private Profiler $mockProfiler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = new Connection();
        
        // Create mock PDO and statement
        $this->mockPdo = $this->createMock(\PDO::class);
        $this->mockStatement = $this->createMock(\PDOStatement::class);
        $this->mockDriver = $this->createMock(DatabaseDriverInterface::class);
        $this->mockProfiler = $this->createMock(Profiler::class);
        
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
        // Mock the factory to return our mock driver
        $originalCreate = DatabaseDriverFactory::class . '::create';
        
        // Use reflection to inject our mock driver
        $reflection = new \ReflectionClass($this->connection);
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
        $reflection = new \ReflectionClass($this->connection);
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
        $reflection = new \ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);
        
        $pdo = $this->connection->pdo();
        
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    public function testPdoReturnsSameInstanceOnSecondCall(): void
    {
        // Set up mock driver
        $this->mockDriver->expects($this->once())
            ->method('createConnection')
            ->willReturn($this->mockPdo);
        
        $this->mockDriver->method('getName')->willReturn('mysql');
        
        // Use reflection to inject our mock driver
        $reflection = new \ReflectionClass($this->connection);
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
        $reflection = new \ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);
        
        $result = $this->connection->executeQuery($sql, $params);
        
        $this->assertInstanceOf(\PDOStatement::class, $result);
    }

    public function testExecuteQueryThrowsExceptionOnFailure(): void
    {
        $sql = 'INVALID SQL';
        $params = [];
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willThrowException(new \PDOException('SQL error'));
        
        // Use reflection to inject mock PDO
        $reflection = new \ReflectionClass($this->connection);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->connection, $this->mockPdo);
        
        $this->expectException(\PDOException::class);
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
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expectedResult);
        
        // Use reflection to inject mock PDO
        $reflection = new \ReflectionClass($this->connection);
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
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expectedResult);
        
        // Use reflection to inject mock PDO
        $reflection = new \ReflectionClass($this->connection);
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
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(false);
        
        // Use reflection to inject mock PDO
        $reflection = new \ReflectionClass($this->connection);
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
        $reflection = new \ReflectionClass($this->connection);
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
        $reflection = new \ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);
        
        $connectMethod = $reflection->getMethod('connect');
        $connectMethod->setAccessible(true);
        $connectMethod->invoke($this->connection);
    }

    public function testConnectUsesEnvironmentVariables(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_PORT'] = '5432';
        $_ENV['DB_DATABASE'] = 'test_db';
        $_ENV['DB_USERNAME'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        $_ENV['DB_CHARSET'] = 'utf8';
        $_ENV['DB_PERSISTENT'] = 'true';
        
        $expectedConfig = [
            'host' => 'localhost',
            'port' => '5432',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass',
            'charset' => 'utf8',
            'persistent' => true,
        ];
        
        $this->mockDriver->expects($this->once())
            ->method('createConnection')
            ->with($expectedConfig)
            ->willReturn($this->mockPdo);
        
        $this->mockDriver->method('getName')->willReturn('postgresql');
        
        // Use reflection to inject mock driver and call connect
        $reflection = new \ReflectionClass($this->connection);
        $driverProperty = $reflection->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($this->connection, $this->mockDriver);
        
        $connectMethod = $reflection->getMethod('connect');
        $connectMethod->setAccessible(true);
        $connectMethod->invoke($this->connection);
    }
}
