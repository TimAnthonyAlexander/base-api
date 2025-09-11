<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Database\Drivers\DatabaseDriverFactory;
use BaseApi\Database\Drivers\MySqlDriver;
use BaseApi\Database\Drivers\SqliteDriver;

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
    
    public function testUnsupportedDriver()
    {
        $this->expectException(\InvalidArgumentException::class);
        DatabaseDriverFactory::create('postgresql');
    }
    
    public function testAvailableDrivers()
    {
        $drivers = DatabaseDriverFactory::getAvailableDrivers();
        $this->assertContains('mysql', $drivers);
        $this->assertContains('sqlite', $drivers);
    }
    
    public function testIsSupported()
    {
        $this->assertTrue(DatabaseDriverFactory::isSupported('mysql'));
        $this->assertTrue(DatabaseDriverFactory::isSupported('sqlite'));
        $this->assertFalse(DatabaseDriverFactory::isSupported('postgresql'));
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
        
        // Test basic type mapping
        $this->assertEquals('INT', $mysqlDriver->phpTypeToSqlType('int'));
        $this->assertEquals('INTEGER', $sqliteDriver->phpTypeToSqlType('int'));
        
        $this->assertEquals('VARCHAR(255)', $mysqlDriver->phpTypeToSqlType('string'));
        $this->assertEquals('TEXT', $sqliteDriver->phpTypeToSqlType('string'));
        
        $this->assertEquals('TINYINT(1)', $mysqlDriver->phpTypeToSqlType('bool'));
        $this->assertEquals('INTEGER', $sqliteDriver->phpTypeToSqlType('bool'));
    }
    
    public function testColumnTypeNormalization()
    {
        $mysqlDriver = new MySqlDriver();
        $sqliteDriver = new SqliteDriver();
        
        // Test MySQL type normalization
        $this->assertEquals('integer', $mysqlDriver->normalizeColumnType('int'));
        $this->assertEquals('varchar', $mysqlDriver->normalizeColumnType('varchar'));
        $this->assertEquals('boolean', $mysqlDriver->normalizeColumnType('tinyint'));
        
        // Test SQLite type normalization
        $this->assertEquals('integer', $sqliteDriver->normalizeColumnType('integer'));
        $this->assertEquals('text', $sqliteDriver->normalizeColumnType('text'));
        $this->assertEquals('real', $sqliteDriver->normalizeColumnType('real'));
    }
}
