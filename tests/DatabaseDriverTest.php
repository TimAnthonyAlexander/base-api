<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Database\Drivers\DatabaseDriverFactory;
use BaseApi\Database\Drivers\MySqlDriver;
use BaseApi\Database\Drivers\SqliteDriver;
use BaseApi\Database\Drivers\PostgreSqlDriver;

class DatabaseDriverTest extends TestCase
{
    public function testMySqlDriverCreation()
    {
        $driver = DatabaseDriverFactory::create('mysql');
        $this->assertInstanceOf(MySqlDriver::class, $driver);
        $this->assertEquals('mysql', $driver->getName());
    }
    
    public function testSqliteDriverCreation()
    {
        $driver = DatabaseDriverFactory::create('sqlite');
        $this->assertInstanceOf(SqliteDriver::class, $driver);
        $this->assertEquals('sqlite', $driver->getName());
    }
    
    public function testPostgreSqlDriverCreation()
    {
        $driver = DatabaseDriverFactory::create('postgresql');
        $this->assertInstanceOf(PostgreSqlDriver::class, $driver);
        $this->assertEquals('postgresql', $driver->getName());
    }
    
    public function testPostgreSqlDriverCreationWithAlias()
    {
        $driver = DatabaseDriverFactory::create('pgsql');
        $this->assertInstanceOf(PostgreSqlDriver::class, $driver);
        $this->assertEquals('postgresql', $driver->getName());
    }
    
    public function testUnsupportedDriver()
    {
        $this->expectException(\InvalidArgumentException::class);
        DatabaseDriverFactory::create('oracle');
    }
    
    public function testAvailableDrivers()
    {
        $drivers = DatabaseDriverFactory::getAvailableDrivers();
        $this->assertContains('mysql', $drivers);
        $this->assertContains('sqlite', $drivers);
        $this->assertContains('postgresql', $drivers);
    }
    
    public function testIsSupported()
    {
        $this->assertTrue(DatabaseDriverFactory::isSupported('mysql'));
        $this->assertTrue(DatabaseDriverFactory::isSupported('sqlite'));
        $this->assertTrue(DatabaseDriverFactory::isSupported('postgresql'));
        $this->assertFalse(DatabaseDriverFactory::isSupported('oracle'));
    }
    
    public function testSqliteConnection()
    {
        $driver = new SqliteDriver();
        $config = ['database' => ':memory:'];
        
        $pdo = $driver->createConnection($config);
        $this->assertInstanceOf(\PDO::class, $pdo);
        
        // Test that foreign keys are enabled
        $stmt = $pdo->query("PRAGMA foreign_keys");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals('1', $result['foreign_keys']);
    }
    
    public function testPhpTypeMapping()
    {
        $mysqlDriver = new MySqlDriver();
        $sqliteDriver = new SqliteDriver();
        $postgresDriver = new PostgreSqlDriver();
        
        // Test basic type mapping
        $this->assertEquals('INT', $mysqlDriver->phpTypeToSqlType('int'));
        $this->assertEquals('INTEGER', $sqliteDriver->phpTypeToSqlType('int'));
        $this->assertEquals('INTEGER', $postgresDriver->phpTypeToSqlType('int'));
        
        $this->assertEquals('VARCHAR(255)', $mysqlDriver->phpTypeToSqlType('string'));
        $this->assertEquals('TEXT', $sqliteDriver->phpTypeToSqlType('string'));
        $this->assertEquals('VARCHAR(255)', $postgresDriver->phpTypeToSqlType('string'));
        
        $this->assertEquals('TINYINT(1)', $mysqlDriver->phpTypeToSqlType('bool'));
        $this->assertEquals('INTEGER', $sqliteDriver->phpTypeToSqlType('bool'));
        $this->assertEquals('BOOLEAN', $postgresDriver->phpTypeToSqlType('bool'));
        
        // Test ID field handling
        $this->assertEquals('VARCHAR(36)', $mysqlDriver->phpTypeToSqlType('string', 'user_id'));
        $this->assertEquals('TEXT', $sqliteDriver->phpTypeToSqlType('string', 'user_id'));
        $this->assertEquals('UUID', $postgresDriver->phpTypeToSqlType('string', 'user_id'));
        
        // Test array/object handling
        $this->assertEquals('JSON', $mysqlDriver->phpTypeToSqlType('array'));
        $this->assertEquals('TEXT', $sqliteDriver->phpTypeToSqlType('array'));
        $this->assertEquals('JSONB', $postgresDriver->phpTypeToSqlType('array'));
    }
    
    public function testColumnTypeNormalization()
    {
        $mysqlDriver = new MySqlDriver();
        $sqliteDriver = new SqliteDriver();
        $postgresDriver = new PostgreSqlDriver();
        
        // Test MySQL type normalization
        $this->assertEquals('integer', $mysqlDriver->normalizeColumnType('int'));
        $this->assertEquals('varchar', $mysqlDriver->normalizeColumnType('varchar'));
        $this->assertEquals('boolean', $mysqlDriver->normalizeColumnType('tinyint'));
        
        // Test SQLite type normalization
        $this->assertEquals('integer', $sqliteDriver->normalizeColumnType('integer'));
        $this->assertEquals('text', $sqliteDriver->normalizeColumnType('text'));
        $this->assertEquals('real', $sqliteDriver->normalizeColumnType('real'));
        
        // Test PostgreSQL type normalization
        $this->assertEquals('integer', $postgresDriver->normalizeColumnType('integer'));
        $this->assertEquals('integer', $postgresDriver->normalizeColumnType('int4'));
        $this->assertEquals('bigint', $postgresDriver->normalizeColumnType('bigint'));
        $this->assertEquals('bigint', $postgresDriver->normalizeColumnType('bigserial'));
        $this->assertEquals('boolean', $postgresDriver->normalizeColumnType('boolean'));
        $this->assertEquals('varchar', $postgresDriver->normalizeColumnType('character varying'));
        $this->assertEquals('jsonb', $postgresDriver->normalizeColumnType('jsonb'));
        $this->assertEquals('uuid', $postgresDriver->normalizeColumnType('uuid'));
    }
}
