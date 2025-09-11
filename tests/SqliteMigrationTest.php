<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Database\Drivers\SqliteDriver;
use BaseApi\Database\Migrations\MigrationPlan;

class SqliteMigrationTest extends TestCase
{
    public function testSqliteCreateTableWithSinglePrimaryKey()
    {
        $driver = new SqliteDriver();
        
        $plan = new MigrationPlan();
        $plan->addOperation('create_table', [
            'table' => 'users',
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
                ],
                [
                    'name' => 'email',
                    'type' => 'TEXT',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => false
                ]
            ],
            'destructive' => false
        ]);
        
        $statements = $driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        
        // Should have column-level PRIMARY KEY
        $this->assertStringContainsString('"id" TEXT PRIMARY KEY NOT NULL', $sql);
        
        // Should NOT have table-level PRIMARY KEY
        $this->assertStringNotContainsString('PRIMARY KEY ("id")', $sql);
        
        // Should not have duplicate PRIMARY KEY
        $this->assertEquals(1, substr_count($sql, 'PRIMARY KEY'));
    }
    
    public function testSqliteCreateTableWithCompositePrimaryKey()
    {
        $driver = new SqliteDriver();
        
        $plan = new MigrationPlan();
        $plan->addOperation('create_table', [
            'table' => 'user_roles',
            'columns' => [
                [
                    'name' => 'user_id',
                    'type' => 'TEXT',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ],
                [
                    'name' => 'role_id',
                    'type' => 'TEXT',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ]
            ],
            'destructive' => false
        ]);
        
        $statements = $driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        
        // Should NOT have column-level PRIMARY KEY
        $this->assertStringNotContainsString('"user_id" TEXT PRIMARY KEY', $sql);
        $this->assertStringNotContainsString('"role_id" TEXT PRIMARY KEY', $sql);
        
        // Should have table-level PRIMARY KEY
        $this->assertStringContainsString('PRIMARY KEY ("user_id", "role_id")', $sql);
        
        // Should have exactly one PRIMARY KEY
        $this->assertEquals(1, substr_count($sql, 'PRIMARY KEY'));
    }
    
    public function testSqliteDefaultValueHandling()
    {
        $driver = new SqliteDriver();
        
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
                    'name' => 'created_at',
                    'type' => 'DATETIME',
                    'nullable' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                    'is_pk' => false
                ],
                [
                    'name' => 'updated_at',
                    'type' => 'DATETIME',
                    'nullable' => true,
                    'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                    'is_pk' => false
                ]
            ],
            'destructive' => false
        ]);
        
        $statements = $driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        
        // Should handle CURRENT_TIMESTAMP
        $this->assertStringContainsString('"created_at" DATETIME DEFAULT CURRENT_TIMESTAMP', $sql);
        
        // Should convert MySQL ON UPDATE syntax to just CURRENT_TIMESTAMP
        $this->assertStringContainsString('"updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP', $sql);
        $this->assertStringNotContainsString('ON UPDATE', $sql);
    }
}
