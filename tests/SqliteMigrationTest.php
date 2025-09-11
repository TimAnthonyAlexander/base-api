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
    
    public function testSqliteCreateTableWithForeignKeys()
    {
        $driver = new SqliteDriver();
        
        $plan = new MigrationPlan();
        
        // Add create table operation
        $plan->addOperation('create_table', [
            'table' => 'posts',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'TEXT',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ],
                [
                    'name' => 'title',
                    'type' => 'TEXT',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => false
                ],
                [
                    'name' => 'user_id',
                    'type' => 'CHAR(36)',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => false
                ]
            ],
            'destructive' => false
        ]);
        
        // Add foreign key operation
        $plan->addOperation('add_fk', [
            'table' => 'posts',
            'fk' => [
                'name' => 'fk_posts_user_id',
                'column' => 'user_id',
                'ref_table' => 'users',
                'ref_column' => 'id',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ],
            'destructive' => false
        ]);
        
        $statements = $driver->generateSql($plan);
        $this->assertCount(1, $statements); // Should only have CREATE TABLE, not separate FK statement
        
        $sql = $statements[0]['sql'];
        
        // Should include foreign key constraint in CREATE TABLE
        $this->assertStringContainsString('FOREIGN KEY ("user_id") REFERENCES "users" ("id")', $sql);
        $this->assertStringContainsString('ON DELETE CASCADE', $sql);
        $this->assertStringContainsString('ON UPDATE CASCADE', $sql);
        
        // Verify complete structure
        $expectedStructure = [
            '"id" TEXT PRIMARY KEY NOT NULL',
            '"title" TEXT NOT NULL',
            '"user_id" CHAR(36) NOT NULL',
            'FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE ON UPDATE CASCADE'
        ];
        
        foreach ($expectedStructure as $expected) {
            $this->assertStringContainsString($expected, $sql);
        }
    }
    
    /**
     * Test SQLite introspection methods to ensure PRAGMA statements work correctly
     * This tests the fix for the "near '?': syntax error" issue
     */
    public function testSqliteIntrospectionMethods()
    {
        $driver = new SqliteDriver();
        $pdo = $driver->createConnection(['database' => ':memory:']);
        
        // Create a test table with various features
        $pdo->exec('
            CREATE TABLE "test_users" (
                "id" TEXT PRIMARY KEY NOT NULL,
                "name" TEXT NOT NULL,
                "email" TEXT NOT NULL,
                "age" INTEGER DEFAULT 0,
                "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // Create an index
        $pdo->exec('CREATE UNIQUE INDEX "idx_test_users_email" ON "test_users" ("email")');
        $pdo->exec('CREATE INDEX "idx_test_users_name" ON "test_users" ("name")');
        
        // Create another table for foreign key testing
        $pdo->exec('
            CREATE TABLE "test_posts" (
                "id" TEXT PRIMARY KEY NOT NULL,
                "user_id" TEXT NOT NULL,
                "title" TEXT NOT NULL,
                FOREIGN KEY ("user_id") REFERENCES "test_users" ("id")
            )
        ');
        
        $dbName = $driver->getDatabaseName($pdo);
        
        // Test getTables
        $tables = $driver->getTables($pdo, $dbName);
        $this->assertContains('test_users', $tables);
        $this->assertContains('test_posts', $tables);
        
        // Test getColumns - this should not throw a syntax error
        $columns = $driver->getColumns($pdo, $dbName, 'test_users');
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('email', $columns);
        $this->assertArrayHasKey('age', $columns);
        $this->assertArrayHasKey('created_at', $columns);
        
        // Verify column properties
        $idColumn = $columns['id'];
        $this->assertEquals('id', $idColumn->name);
        $this->assertTrue($idColumn->is_pk);
        $this->assertFalse($idColumn->nullable);
        
        $ageColumn = $columns['age'];
        $this->assertEquals('age', $ageColumn->name);
        $this->assertFalse($ageColumn->is_pk);
        $this->assertTrue($ageColumn->nullable);
        $this->assertEquals('0', $ageColumn->default);
        
        // Test getIndexes - this should not throw a syntax error
        $indexes = $driver->getIndexes($pdo, $dbName, 'test_users');
        $this->assertArrayHasKey('idx_test_users_email', $indexes);
        $this->assertArrayHasKey('idx_test_users_name', $indexes);
        
        // Verify index properties
        $emailIndex = $indexes['idx_test_users_email'];
        $this->assertEquals('idx_test_users_email', $emailIndex->name);
        $this->assertEquals('email', $emailIndex->column);
        $this->assertEquals('unique', $emailIndex->type);
        
        $nameIndex = $indexes['idx_test_users_name'];
        $this->assertEquals('idx_test_users_name', $nameIndex->name);
        $this->assertEquals('name', $nameIndex->column);
        $this->assertEquals('index', $nameIndex->type);
        
        // Test getForeignKeys - this should not throw a syntax error
        $foreignKeys = $driver->getForeignKeys($pdo, $dbName, 'test_posts');
        $this->assertCount(1, $foreignKeys);
        
        // Verify foreign key properties
        $fk = reset($foreignKeys);
        $this->assertEquals('user_id', $fk->column);
        $this->assertEquals('test_users', $fk->ref_table);
        $this->assertEquals('id', $fk->ref_column);
    }
    
    /**
     * Test that PRAGMA statements work with table names containing special characters
     */
    public function testSqliteIntrospectionWithSpecialTableNames()
    {
        $driver = new SqliteDriver();
        $pdo = $driver->createConnection(['database' => ':memory:']);
        
        // Create tables with names that might cause issues if not properly quoted
        $pdo->exec('CREATE TABLE "user-profiles" ("id" TEXT PRIMARY KEY, "data" TEXT)');
        $pdo->exec('CREATE TABLE "order items" ("id" TEXT PRIMARY KEY, "name" TEXT)');
        $pdo->exec('CREATE INDEX "idx_user-profiles_data" ON "user-profiles" ("data")');
        
        $dbName = $driver->getDatabaseName($pdo);
        
        // These should not throw syntax errors even with special table names
        $tables = $driver->getTables($pdo, $dbName);
        $this->assertContains('user-profiles', $tables);
        $this->assertContains('order items', $tables);
        
        // Test introspection on table with hyphen in name
        $columns = $driver->getColumns($pdo, $dbName, 'user-profiles');
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('data', $columns);
        
        $indexes = $driver->getIndexes($pdo, $dbName, 'user-profiles');
        $this->assertArrayHasKey('idx_user-profiles_data', $indexes);
        
        // Test introspection on table with space in name
        $columns = $driver->getColumns($pdo, $dbName, 'order items');
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('name', $columns);
    }
}
