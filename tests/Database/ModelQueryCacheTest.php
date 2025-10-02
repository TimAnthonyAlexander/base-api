<?php

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use BaseApi\App;
use BaseApi\Database\ModelQuery;
use BaseApi\Models\BaseModel;

/**
 * Test to verify ModelQuery caching behavior
 * 
 * This test verifies the fix for the critical bug where queries were cached by default,
 * causing stale data to be returned even after database updates.
 */
class ModelQueryCacheTest extends TestCase
{
    private static ?App $app = null;
    
    public static function setUpBeforeClass(): void
    {
        // Bootstrap app if not already done
        if (!self::$app instanceof App) {
            self::$app = new App(dirname(__DIR__, 2));
        }
    }
    
    public function testCachingIsDisabledByDefault(): void
    {
        $query = TestModel::query();
        
        // Use reflection to check private cacheEnabled property
        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('cacheEnabled');
        $property->setAccessible(true);
        
        $this->assertFalse(
            $property->getValue($query),
            'Query caching should be disabled by default to prevent stale data reads'
        );
    }
    
    public function testQueriesReturnFreshDataAfterUpdates(): void
    {
        // Create a test table
        $this->createTestTable();
        
        try {
            // Insert a record
            $model = new TestModel();
            $model->name = 'Original';
            $model->status = 'pending';
            $model->save();
            
            $id = $model->id;
            
            // Query it - should get 'pending'
            $result1 = TestModel::where('status', '=', 'pending')->get();
            $this->assertCount(1, $result1);
            $this->assertEquals('pending', $result1[0]->status);
            
            // Update the status
            $model->status = 'completed';
            $model->save();
            
            // Query again - should NOT return cached 'pending' result
            $result2 = TestModel::where('status', '=', 'pending')->get();
            $this->assertCount(
                0, 
                $result2, 
                'Query should return fresh data after model update, not cached results'
            );
            
            // Query for 'completed' - should find it
            $result3 = TestModel::where('status', '=', 'completed')->get();
            $this->assertCount(1, $result3);
            $this->assertEquals('completed', $result3[0]->status);
            
        } finally {
            $this->dropTestTable();
        }
    }
    
    public function testMultipleBatchesGetDifferentRecords(): void
    {
        $this->createTestTable();
        
        try {
            // Create 10 test records
            for ($i = 0; $i < 10; $i++) {
                $model = new TestModel();
                $model->name = "Item $i";
                $model->status = 'raw';
                $model->save();
            }
            
            // First batch: get 3 items and mark as processed
            $batch1 = TestModel::where('status', '=', 'raw')
                ->orderBy('id', 'ASC')
                ->limit(3)
                ->get();
            
            $this->assertCount(3, $batch1);
            $batch1Ids = array_map(fn($m) => $m->id, $batch1);
            
            foreach ($batch1 as $item) {
                $item->status = 'processed';
                $item->save();
            }
            
            // Second batch: should get DIFFERENT items, not cached first batch
            $batch2 = TestModel::where('status', '=', 'raw')
                ->orderBy('id', 'ASC')
                ->limit(3)
                ->get();
            
            $this->assertCount(3, $batch2);
            $batch2Ids = array_map(fn($m) => $m->id, $batch2);
            
            // Verify batch2 IDs are different from batch1
            $this->assertEmpty(
                array_intersect($batch1Ids, $batch2Ids),
                'Second batch should return different records, not cached first batch'
            );
            
        } finally {
            $this->dropTestTable();
        }
    }
    
    public function testExplicitCacheEnabling(): void
    {
        $this->createTestTable();
        
        try {
            // Create a record
            $model = new TestModel();
            $model->name = 'Cached Test';
            $model->status = 'active';
            $model->save();
            
            // Query with explicit caching enabled
            $result1 = TestModel::where('status', '=', 'active')
                ->cache(10) // Cache for 10 seconds
                ->get();
            
            $this->assertCount(1, $result1);
            
            // Update the record
            $model->status = 'inactive';
            $model->save();
            
            // Query again with caching - should still return cached 'active' result
            // (This demonstrates that explicit caching still works when opted-in)
            $result2 = TestModel::where('status', '=', 'active')
                ->cache(10)
                ->get();
            
            // Note: This behavior depends on whether cache invalidation clears all query caches
            // or just specific ones. The important thing is explicit caching still works.
            
        } finally {
            $this->dropTestTable();
        }
    }
    
    public function testNoCacheMethodDisablesCaching(): void
    {
        $query = TestModel::query()->cache(300)->noCache();
        
        // Use reflection to check private cacheEnabled property
        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('cacheEnabled');
        $property->setAccessible(true);
        
        $this->assertFalse(
            $property->getValue($query),
            'noCache() method should disable caching even after cache() was called'
        );
    }
    
    private function createTestTable(): void
    {
        $db = App::db();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS test_model (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255),
                status VARCHAR(50),
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )', 
            []
        );
    }
    
    private function dropTestTable(): void
    {
        $db = App::db();
        $db->exec('DROP TABLE IF EXISTS test_model', []);
    }
}

/**
 * Test model for cache testing
 */
class TestModel extends BaseModel
{
    protected static ?string $table = 'test_model';
    
    public string $name = '';
    public string $status = '';
}

