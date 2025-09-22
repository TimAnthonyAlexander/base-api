<?php

namespace BaseApi\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Database\Drivers\PostgreSqlDriver;
use BaseApi\Database\Migrations\MigrationPlan;
use BaseApi\Database\DbException;

class PostgreSqlDriverTest extends TestCase
{
    private PostgreSqlDriver $driver;
    
    #[Override]
    protected function setUp(): void
    {
        $this->driver = new PostgreSqlDriver();
    }
    
    public function testDriverName(): void
    {
        $this->assertEquals('postgresql', $this->driver->getName());
    }
    
    public function testConnectionCreation(): void
    {
        // Test with minimal config
        $config = [
            'host' => 'localhost',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ];
        
        // We can't actually test connection without a real PostgreSQL instance
        // But we can test that the method exists and handles configuration properly
        $this->expectException(DbException::class);
        $this->driver->createConnection($config);
    }
    
    public function testConnectionWithFullConfig(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => '5432',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass',
            'charset' => 'utf8',
            'persistent' => true,
            'sslmode' => 'require',
            'schema' => 'custom_schema'
        ];
        
        // Test that configuration is properly handled
        $this->expectException(DbException::class);
        $this->driver->createConnection($config);
    }
    
    public function testPhpTypeToSqlTypeMapping(): void
    {
        // Test basic type mapping
        $this->assertEquals('BOOLEAN', $this->driver->phpTypeToSqlType('bool'));
        $this->assertEquals('BOOLEAN', $this->driver->phpTypeToSqlType('boolean'));
        
        $this->assertEquals('INTEGER', $this->driver->phpTypeToSqlType('int'));
        $this->assertEquals('SERIAL', $this->driver->phpTypeToSqlType('int', 'user_id'));
        $this->assertEquals('SERIAL', $this->driver->phpTypeToSqlType('integer', 'id'));
        
        $this->assertEquals('REAL', $this->driver->phpTypeToSqlType('float'));
        $this->assertEquals('DOUBLE PRECISION', $this->driver->phpTypeToSqlType('double'));
        
        $this->assertEquals('VARCHAR(255)', $this->driver->phpTypeToSqlType('string'));
        $this->assertEquals('UUID', $this->driver->phpTypeToSqlType('string', 'user_id'));
        $this->assertEquals('UUID', $this->driver->phpTypeToSqlType('string', 'id'));
        
        $this->assertEquals('JSONB', $this->driver->phpTypeToSqlType('array'));
        $this->assertEquals('JSONB', $this->driver->phpTypeToSqlType('object'));
        
        $this->assertEquals('TIMESTAMP', $this->driver->phpTypeToSqlType('DateTime'));
        $this->assertEquals('TIMESTAMP', $this->driver->phpTypeToSqlType('\\DateTime'));
        
        $this->assertEquals('TEXT', $this->driver->phpTypeToSqlType('unknown'));
    }
    
    public function testColumnTypeNormalization(): void
    {
        // Test PostgreSQL type normalization
        $this->assertEquals('boolean', $this->driver->normalizeColumnType('boolean'));
        $this->assertEquals('boolean', $this->driver->normalizeColumnType('bool'));
        
        $this->assertEquals('smallint', $this->driver->normalizeColumnType('smallint'));
        $this->assertEquals('smallint', $this->driver->normalizeColumnType('int2'));
        
        $this->assertEquals('integer', $this->driver->normalizeColumnType('integer'));
        $this->assertEquals('integer', $this->driver->normalizeColumnType('int'));
        $this->assertEquals('integer', $this->driver->normalizeColumnType('int4'));
        $this->assertEquals('integer', $this->driver->normalizeColumnType('serial'));
        
        $this->assertEquals('bigint', $this->driver->normalizeColumnType('bigint'));
        $this->assertEquals('bigint', $this->driver->normalizeColumnType('int8'));
        $this->assertEquals('bigint', $this->driver->normalizeColumnType('bigserial'));
        
        $this->assertEquals('decimal', $this->driver->normalizeColumnType('decimal'));
        $this->assertEquals('decimal', $this->driver->normalizeColumnType('numeric'));
        
        $this->assertEquals('real', $this->driver->normalizeColumnType('real'));
        $this->assertEquals('real', $this->driver->normalizeColumnType('float4'));
        
        $this->assertEquals('double', $this->driver->normalizeColumnType('double precision'));
        $this->assertEquals('double', $this->driver->normalizeColumnType('float8'));
        
        $this->assertEquals('char', $this->driver->normalizeColumnType('character'));
        $this->assertEquals('char', $this->driver->normalizeColumnType('char'));
        
        $this->assertEquals('varchar', $this->driver->normalizeColumnType('character varying'));
        $this->assertEquals('varchar', $this->driver->normalizeColumnType('varchar'));
        
        $this->assertEquals('text', $this->driver->normalizeColumnType('text'));
        
        $this->assertEquals('bytea', $this->driver->normalizeColumnType('bytea'));
        
        $this->assertEquals('date', $this->driver->normalizeColumnType('date'));
        $this->assertEquals('time', $this->driver->normalizeColumnType('time'));
        $this->assertEquals('time', $this->driver->normalizeColumnType('time without time zone'));
        
        $this->assertEquals('timestamp', $this->driver->normalizeColumnType('timestamp'));
        $this->assertEquals('timestamp', $this->driver->normalizeColumnType('timestamp without time zone'));
        $this->assertEquals('timestamptz', $this->driver->normalizeColumnType('timestamp with time zone'));
        $this->assertEquals('timestamptz', $this->driver->normalizeColumnType('timestamptz'));
        
        $this->assertEquals('interval', $this->driver->normalizeColumnType('interval'));
        
        $this->assertEquals('json', $this->driver->normalizeColumnType('json'));
        $this->assertEquals('jsonb', $this->driver->normalizeColumnType('jsonb'));
        
        $this->assertEquals('uuid', $this->driver->normalizeColumnType('uuid'));
        
        $this->assertEquals('inet', $this->driver->normalizeColumnType('inet'));
        $this->assertEquals('cidr', $this->driver->normalizeColumnType('cidr'));
        $this->assertEquals('macaddr', $this->driver->normalizeColumnType('macaddr'));
        
        $this->assertEquals('array', $this->driver->normalizeColumnType('array'));
        
        // Test with UDT names (for SERIAL types)
        $this->assertEquals('integer', $this->driver->normalizeColumnType('integer', 'int4'));
        $this->assertEquals('integer', $this->driver->normalizeColumnType('integer', 'serial4'));
        $this->assertEquals('bigint', $this->driver->normalizeColumnType('bigint', 'int8'));
        $this->assertEquals('bigint', $this->driver->normalizeColumnType('bigint', 'bigserial'));
        $this->assertEquals('bigint', $this->driver->normalizeColumnType('bigint', 'serial8'));
    }
    
    public function testDefaultValueNormalization(): void
    {
        // Test null defaults
        $this->assertNull($this->driver->normalizeDefault(null));
        $this->assertNull($this->driver->normalizeDefault('NULL'));
        
        // Test timestamp defaults
        $this->assertEquals('CURRENT_TIMESTAMP', $this->driver->normalizeDefault('CURRENT_TIMESTAMP'));
        $this->assertEquals('CURRENT_TIMESTAMP', $this->driver->normalizeDefault('now()'));
        
        // Test sequence defaults (SERIAL)
        $this->assertEquals("nextval('users_id_seq'::regclass)", 
            $this->driver->normalizeDefault("nextval('users_id_seq'::regclass)"));
        
        // Test boolean defaults
        $this->assertEquals('true', $this->driver->normalizeDefault('true'));
        $this->assertEquals('false', $this->driver->normalizeDefault('false'));
        
        // Test string defaults with quotes
        $this->assertEquals('default_value', $this->driver->normalizeDefault("'default_value'"));
        $this->assertEquals('default_value', $this->driver->normalizeDefault('"default_value"'));
        
        // Test PostgreSQL type casting removal
        $this->assertEquals('default_value', $this->driver->normalizeDefault("'default_value'::text"));
        $this->assertEquals('default_value', $this->driver->normalizeDefault("'default_value'::varchar"));
        
        // Test numeric defaults
        $this->assertEquals('0', $this->driver->normalizeDefault('0'));
        $this->assertEquals('42', $this->driver->normalizeDefault('42'));
    }
    
    public function testCreateTableGeneration(): void
    {
        $plan = new MigrationPlan();
        $plan->addOperation('create_table', [
            'table' => 'users',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'SERIAL',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ],
                [
                    'name' => 'name',
                    'type' => 'VARCHAR(255)',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => false
                ],
                [
                    'name' => 'email',
                    'type' => 'VARCHAR(255)',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => false
                ],
                [
                    'name' => 'created_at',
                    'type' => 'TIMESTAMP',
                    'nullable' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                    'is_pk' => false
                ]
            ],
            'destructive' => false
        ]);
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        
        // Should have proper PostgreSQL syntax
        $this->assertStringContainsString('CREATE TABLE "users"', $sql);
        $this->assertStringContainsString('"id" SERIAL NOT NULL', $sql);
        $this->assertStringContainsString('"name" VARCHAR(255) NOT NULL', $sql);
        $this->assertStringContainsString('"email" VARCHAR(255) NOT NULL', $sql);
        $this->assertStringContainsString('"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP', $sql);
        $this->assertStringContainsString('PRIMARY KEY ("id")', $sql);
        
        // Verify metadata
        $this->assertEquals('Create table users', $statements[0]['description']);
        $this->assertFalse($statements[0]['destructive']);
    }
    
    public function testCreateTableWithCompositePrimaryKey(): void
    {
        $plan = new MigrationPlan();
        $plan->addOperation('create_table', [
            'table' => 'user_roles',
            'columns' => [
                [
                    'name' => 'user_id',
                    'type' => 'UUID',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ],
                [
                    'name' => 'role_id',
                    'type' => 'UUID',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ]
            ],
            'destructive' => false
        ]);
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        
        // Should have composite primary key
        $this->assertStringContainsString('PRIMARY KEY ("user_id", "role_id")', $sql);
    }
    
    public function testAddColumnGeneration(): void
    {
        $plan = new MigrationPlan();
        $plan->addOperation('add_column', [
            'table' => 'users',
            'column' => [
                'name' => 'phone',
                'type' => 'VARCHAR(20)',
                'nullable' => true,
                'default' => null,
                'is_pk' => false
            ],
            'destructive' => false
        ]);
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        $this->assertEquals('ALTER TABLE "users" ADD COLUMN "phone" VARCHAR(20)', $sql);
    }
    
    public function testModifyColumnGeneration(): void
    {
        $plan = new MigrationPlan();
        $plan->addOperation('modify_column', [
            'table' => 'users',
            'column' => [
                'name' => 'name',
                'type' => 'TEXT',
                'nullable' => false,
                'default' => 'Unknown',
                'is_pk' => false
            ],
            'destructive' => false
        ]);
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        
        // PostgreSQL requires separate ALTER statements
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "name" TYPE TEXT', $sql);
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "name" SET NOT NULL', $sql);
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "name" SET DEFAULT \'Unknown\'', $sql);
    }
    
    public function testAddIndexGeneration(): void
    {
        $plan = new MigrationPlan();
        $plan->addOperation('add_index', [
            'table' => 'users',
            'index' => [
                'name' => 'idx_users_email',
                'column' => 'email',
                'type' => 'unique'
            ],
            'destructive' => false
        ]);
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        $this->assertEquals('CREATE UNIQUE INDEX "idx_users_email" ON "users" ("email")', $sql);
    }
    
    public function testAddForeignKeyGeneration(): void
    {
        $plan = new MigrationPlan();
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
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(1, $statements);
        
        $sql = $statements[0]['sql'];
        $expected = 'ALTER TABLE "posts" ADD CONSTRAINT "fk_posts_user_id" ' .
                   'FOREIGN KEY ("user_id") REFERENCES "users" ("id") ' .
                   'ON DELETE CASCADE ON UPDATE CASCADE';
        $this->assertEquals($expected, $sql);
    }
    
    public function testDropOperations(): void
    {
        $plan = new MigrationPlan();
        
        // Add drop operations
        $plan->addOperation('drop_fk', [
            'table' => 'posts',
            'fk_name' => 'fk_posts_user_id'
        ]);
        
        $plan->addOperation('drop_index', [
            'index' => 'idx_users_email'
        ]);
        
        $plan->addOperation('drop_column', [
            'table' => 'users',
            'column' => 'phone'
        ]);
        
        $plan->addOperation('drop_table', [
            'table' => 'old_table'
        ]);
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(4, $statements);
        
        // Verify drop statements
        $this->assertEquals('ALTER TABLE "posts" DROP CONSTRAINT "fk_posts_user_id"', $statements[0]['sql']);
        $this->assertEquals('DROP INDEX IF EXISTS "idx_users_email"', $statements[1]['sql']);
        $this->assertEquals('ALTER TABLE "users" DROP COLUMN "phone"', $statements[2]['sql']);
        $this->assertEquals('DROP TABLE IF EXISTS "old_table"', $statements[3]['sql']);
        
        // Verify all drop operations are marked as destructive
        foreach ($statements as $statement) {
            $this->assertTrue($statement['destructive']);
        }
    }
    
    public function testComplexMigrationPlan(): void
    {
        $plan = new MigrationPlan();
        
        // Add operations in mixed order to test proper ordering
        $plan->addOperation('drop_table', ['table' => 'old_table']);
        $plan->addOperation('create_table', [
            'table' => 'new_table',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'SERIAL',
                    'nullable' => false,
                    'default' => null,
                    'is_pk' => true
                ]
            ]
        ]);
        $plan->addOperation('add_column', [
            'table' => 'existing_table',
            'column' => [
                'name' => 'new_column',
                'type' => 'TEXT',
                'nullable' => true,
                'default' => null,
                'is_pk' => false
            ]
        ]);
        
        $statements = $this->driver->generateSql($plan);
        $this->assertCount(3, $statements);
        
        // Verify proper ordering: creates first, then adds, then drops
        $this->assertStringContainsString('CREATE TABLE "new_table"', $statements[0]['sql']);
        $this->assertStringContainsString('ALTER TABLE "existing_table" ADD COLUMN', $statements[1]['sql']);
        $this->assertStringContainsString('DROP TABLE IF EXISTS "old_table"', $statements[2]['sql']);
    }
}
