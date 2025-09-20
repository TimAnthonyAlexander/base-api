<?php

namespace BaseApi\Tests\Integration;

use PHPUnit\Framework\TestCase;
use BaseApi\Database\Migrations\ModelScanner;
use BaseApi\Database\Migrations\SqlGenerator;
use BaseApi\Database\Migrations\MigrationPlan;
use BaseApi\Database\Drivers\MySqlDriver;
use BaseApi\Database\Drivers\SqliteDriver;

class MigrationIntegrationTest extends TestCase
{
    public function testSqlGeneration()
    {
        $sqliteDriver = new SqliteDriver();
        $mysqlDriver = new MySqlDriver();
        
        // Create a simple migration plan
        $plan = new MigrationPlan();
        $plan->addOperation('create_table', [
            'table' => 'test_table',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'TEXT',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ],
                [
                    'name' => 'name',
                    'type' => 'TEXT',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => false
                ]
            ],
            'destructive' => false
        ]);
        
        // Test SQLite SQL generation
        $sqliteStatements = $sqliteDriver->generateSql($plan);
        $this->assertCount(1, $sqliteStatements);
        $this->assertStringContainsString('CREATE TABLE "test_table"', $sqliteStatements[0]['sql']);
        $this->assertStringContainsString('"id" TEXT PRIMARY KEY NOT NULL', $sqliteStatements[0]['sql']);
        
        // Test MySQL SQL generation  
        $mysqlStatements = $mysqlDriver->generateSql($plan);
        $this->assertCount(1, $mysqlStatements);
        $this->assertStringContainsString('CREATE TABLE `test_table`', $mysqlStatements[0]['sql']);
        $this->assertStringContainsString('`id` TEXT NOT NULL', $mysqlStatements[0]['sql']);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $mysqlStatements[0]['sql']);
    }
    
    public function testDriverTypeMapping()
    {
        $sqliteDriver = new SqliteDriver();
        $mysqlDriver = new MySqlDriver();
        
        // Test string type mapping for ID fields
        $this->assertEquals('TEXT', $sqliteDriver->phpTypeToSqlType('string', 'id'));
        $this->assertEquals('VARCHAR(36)', $mysqlDriver->phpTypeToSqlType('string', 'id'));
        
        // Test regular string fields
        $this->assertEquals('TEXT', $sqliteDriver->phpTypeToSqlType('string', 'name'));
        $this->assertEquals('VARCHAR(255)', $mysqlDriver->phpTypeToSqlType('string', 'name'));
        
        // Test numeric types
        $this->assertEquals('INTEGER', $sqliteDriver->phpTypeToSqlType('int'));
        $this->assertEquals('INT', $mysqlDriver->phpTypeToSqlType('int'));
        
        $this->assertEquals('REAL', $sqliteDriver->phpTypeToSqlType('float'));
        $this->assertEquals('DOUBLE', $mysqlDriver->phpTypeToSqlType('float'));
        
        // Test boolean types
        $this->assertEquals('INTEGER', $sqliteDriver->phpTypeToSqlType('bool'));
        $this->assertEquals('TINYINT(1)', $mysqlDriver->phpTypeToSqlType('bool'));
    }
    
    public function testAddColumnOperation()
    {
        $sqliteDriver = new SqliteDriver();
        $mysqlDriver = new MySqlDriver();
        
        $plan = new MigrationPlan();
        $plan->addOperation('add_column', [
            'table' => 'users',
            'column' => [
                'name' => 'email',
                'type' => 'VARCHAR(255)',
                'nullable' => false,
                'default' => null,
                'is_pk' => false
            ],
            'destructive' => false
        ]);
        
        // Test SQLite ADD COLUMN
        $sqliteStatements = $sqliteDriver->generateSql($plan);
        $this->assertCount(1, $sqliteStatements);
        $this->assertStringContainsString('ALTER TABLE "users" ADD COLUMN', $sqliteStatements[0]['sql']);
        $this->assertStringContainsString('"email" VARCHAR(255) NOT NULL', $sqliteStatements[0]['sql']);
        
        // Test MySQL ADD COLUMN
        $mysqlStatements = $mysqlDriver->generateSql($plan);
        $this->assertCount(1, $mysqlStatements);
        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN', $mysqlStatements[0]['sql']);
        $this->assertStringContainsString('`email` VARCHAR(255) NOT NULL', $mysqlStatements[0]['sql']);
    }
    
    public function testDropTableOperation()
    {
        $sqliteDriver = new SqliteDriver();
        $mysqlDriver = new MySqlDriver();
        
        $plan = new MigrationPlan();
        $plan->addOperation('drop_table', [
            'table' => 'old_table',
            'destructive' => true
        ]);
        
        // Test SQLite DROP TABLE
        $sqliteStatements = $sqliteDriver->generateSql($plan);
        $this->assertCount(1, $sqliteStatements);
        $this->assertEquals('DROP TABLE IF EXISTS "old_table"', $sqliteStatements[0]['sql']);
        $this->assertTrue($sqliteStatements[0]['destructive']);
        
        // Test MySQL DROP TABLE
        $mysqlStatements = $mysqlDriver->generateSql($plan);
        $this->assertCount(1, $mysqlStatements);
        $this->assertEquals('DROP TABLE IF EXISTS `old_table`', $mysqlStatements[0]['sql']);
        $this->assertTrue($mysqlStatements[0]['destructive']);
    }
}