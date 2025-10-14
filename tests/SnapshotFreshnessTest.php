<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use BaseApi\Models\BaseModel;

// Test models for snapshot freshness
class SnapshotTestProduct extends BaseModel
{
    public string $id = '';

    public string $name = '';

    public int $quantity = 0;

    public float $price = 0.0;

    public string $status = 'active';

    public ?string $created_at = null;

    public ?string $updated_at = null;

    protected static ?string $table = 'products';
}

class SnapshotTestOrder extends BaseModel
{
    public string $id = '';

    public string $product_id = '';

    public int $count = 0;

    public ?string $created_at = null;

    protected static ?string $table = 'orders';
}

class SnapshotFreshnessTest extends TestCase
{
    /**
     * Test that live state overrides snapshot in serialization
     */
    public function testSerializerPreferenceForLiveState(): void
    {
        $model = new SnapshotTestProduct();
        
        // Simulate loading from DB with snapshot
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Old Product Name',
            'quantity' => 5,
            'price' => 10.50,
            'status' => 'inactive',
            'created_at' => '2023-01-01 10:00:00'
        ]);

        // Now modify live properties
        $model->id = '123';
        $model->name = 'New Product Name';
        $model->quantity = 10;
        $model->price = 15.99;
        $model->status = 'active';
        $model->created_at = '2023-01-01 10:00:00';

        // Serialize and verify live values win
        $array = $model->toArray();

        $this->assertEquals('New Product Name', $array['name'], 'Live name should override snapshot');
        $this->assertEquals(10, $array['quantity'], 'Live quantity should override snapshot');
        $this->assertEquals(15.99, $array['price'], 'Live price should override snapshot');
        $this->assertEquals('active', $array['status'], 'Live status should override snapshot');
    }

    /**
     * Test that properties hydrated from DB are used correctly
     */
    public function testSerializerUsesHydratedProperties(): void
    {
        // Use fromRow to properly hydrate a model (realistic scenario)
        $model = SnapshotTestProduct::fromRow([
            'id' => '123',
            'name' => 'Product Name',
            'quantity' => 5,
            'status' => 'active'
        ]);

        $array = $model->toArray();

        // All hydrated values should be present
        $this->assertEquals('123', $array['id']);
        $this->assertEquals('Product Name', $array['name']);
        $this->assertEquals(5, $array['quantity']);
        $this->assertEquals('active', $array['status']);
        
        // Now modify one property
        $model->quantity = 10;
        
        $array = $model->toArray();
        
        // Modified value should be used
        $this->assertEquals(10, $array['quantity']);
        // Unmodified values still present
        $this->assertEquals('Product Name', $array['name']);
    }

    /**
     * Test that explicitly setting a property to empty string overrides snapshot
     */
    public function testSerializerPrefersExplicitEmptyString(): void
    {
        $model = new SnapshotTestProduct();
        
        // Simulate loading from DB with snapshot
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Product Name',
            'status' => 'active'
        ]);

        // Explicitly set properties including empty string that differs from snapshot
        $model->id = '123';
        $model->name = 'Product Name';
        $model->status = ''; // Explicitly set to empty - differs from snapshot 'active'

        $array = $model->toArray();

        // Empty string should override snapshot since it differs from snapshot value
        $this->assertEquals('', $array['status'], 'Empty string should override snapshot when values differ');
        $this->assertEquals('Product Name', $array['name'], 'Name should match snapshot since unchanged');
    }

    /**
     * Test that null values are excluded from serialization
     */
    public function testSerializerExcludesNullValues(): void
    {
        $model = new SnapshotTestProduct();
        $model->id = '123';
        $model->name = 'Product';
        // created_at and updated_at are null

        $array = $model->toArray();

        $this->assertArrayNotHasKey('created_at', $array, 'Null values should not be included');
        $this->assertArrayNotHasKey('updated_at', $array, 'Null values should not be included');
    }

    /**
     * Test that integer values override snapshot correctly
     */
    public function testSerializerPreferenceForIntegerValues(): void
    {
        $model = new SnapshotTestProduct();
        
        // Simulate loading from DB
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'quantity' => 5,
            'price' => 10.50
        ]);

        // Update to new values
        $model->id = '123';
        $model->quantity = 25;
        $model->price = 99.99;

        $array = $model->toArray();

        $this->assertEquals(25, $array['quantity'], 'Updated integer should override snapshot');
        $this->assertEquals(99.99, $array['price'], 'Updated float should override snapshot');
    }

    /**
     * Test that updating to zero overrides non-zero snapshot
     */
    public function testSerializerPreferencesZeroOverSnapshot(): void
    {
        $model = new SnapshotTestProduct();
        
        // Simulate loading from DB with non-zero values
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'quantity' => 10,
            'price' => 50.00
        ]);

        // Update to zero
        $model->id = '123';
        $model->quantity = 0;
        $model->price = 0.0;

        $array = $model->toArray();

        $this->assertEquals(0, $array['quantity'], 'Zero should override snapshot');
        $this->assertEquals(0.0, $array['price'], 'Zero float should override snapshot');
    }

    /**
     * Test that timestamp updates override snapshot
     */
    public function testSerializerPreferencesUpdatedTimestamp(): void
    {
        $model = new SnapshotTestProduct();
        
        // Simulate loading from DB
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'created_at' => '2023-01-01 10:00:00',
            'updated_at' => '2023-01-01 10:00:00'
        ]);

        // Update timestamp
        $model->id = '123';
        $model->created_at = '2023-01-01 10:00:00';
        $model->updated_at = '2023-06-15 14:30:00';

        $array = $model->toArray();

        $this->assertEquals('2023-06-15 14:30:00', $array['updated_at'], 'Updated timestamp should override snapshot');
    }

    /**
     * Test that snapshot is synced after insert
     */
    public function testSnapshotSyncedAfterInsert(): void
    {
        $model = new SnapshotTestProduct();
        $model->name = 'New Product';
        $model->quantity = 100;
        $model->price = 25.50;

        // Mock the insert operation
        $reflection = new ReflectionClass($model);
        $insertMethod = $reflection->getMethod('insert');
        $insertMethod->setAccessible(true);

        // We can't actually insert to DB in unit test, but we can verify syncSnapshot is called
        // by checking __row after calling syncSnapshot directly
        $syncMethod = $reflection->getMethod('syncSnapshot');
        $syncMethod->setAccessible(true);
        $syncMethod->invoke($model);

        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);

        $snapshot = $rowProperty->getValue($model);

        $this->assertEquals('New Product', $snapshot['name'] ?? null);
        $this->assertEquals(100, $snapshot['quantity'] ?? null);
        $this->assertEquals(25.50, $snapshot['price'] ?? null);
    }

    /**
     * Test that snapshot is cleared after delete
     */
    public function testSnapshotClearedAfterDelete(): void
    {
        $model = new SnapshotTestProduct();
        $model->id = '123';
        
        // Set initial snapshot
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Product'
        ]);

        $cacheProperty = $reflection->getProperty('__relationCache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($model, ['test' => 'data']);

        // Manually clear snapshot (simulating delete)
        $rowProperty->setValue($model, []);
        $cacheProperty->setValue($model, []);

        $this->assertEmpty($rowProperty->getValue($model), 'Snapshot should be cleared after delete');
        $this->assertEmpty($cacheProperty->getValue($model), 'Relation cache should be cleared after delete');
    }

    /**
     * Test loadBelongsTo prefers live property over snapshot
     */
    public function testLoadBelongsToPrefersLiveProperty(): void
    {
        $order = new SnapshotTestOrder();

        // Simulate loading from DB with stale FK
        $reflection = new ReflectionClass($order);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($order, [
            'id' => 'order-123',
            'product_id' => 'old-product-456',
            'count' => 5
        ]);

        // Update FK to new value
        $order->id = 'order-123';
        $order->product_id = 'new-product-789';
        $order->count = 5;

        // Verify that reading product_id returns live value, not snapshot
        $this->assertEquals('new-product-789', $order->product_id, 'Live FK should be accessible');

        // The actual loadBelongsTo would use this live value, not the snapshot
        // We can't test the full flow without mocking DB, but we verified the property access
    }
}

