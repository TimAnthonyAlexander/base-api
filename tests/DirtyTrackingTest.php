<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use BaseApi\Models\BaseModel;

// Test model for dirty tracking
class DirtyTestProduct extends BaseModel
{
    public string $id = '';

    public string $name = '';

    public int $quantity = 0;

    public float $price = 0.0;

    public ?string $description = null;

    protected static ?string $table = 'products';
}

class DirtyTrackingTest extends TestCase
{
    /**
     * Test isDirty returns true when property is modified
     */
    public function testIsDirtyDetectsModifiedProperty(): void
    {
        $model = new DirtyTestProduct();
        
        // Simulate loading from DB
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Original Name',
            'quantity' => 10,
            'price' => 50.00
        ]);

        // Initially not dirty (snapshot matches)
        $model->id = '123';
        $model->name = 'Original Name';
        $model->quantity = 10;
        $model->price = 50.00;

        $this->assertFalse($model->isDirty(), 'Model should not be dirty when properties match snapshot');
        $this->assertFalse($model->isDirty('name'), 'Name should not be dirty');

        // Modify a property
        $model->name = 'Modified Name';

        $this->assertTrue($model->isDirty(), 'Model should be dirty after modification');
        $this->assertTrue($model->isDirty('name'), 'Name should be dirty after modification');
        $this->assertFalse($model->isDirty('quantity'), 'Quantity should not be dirty');
    }

    /**
     * Test getDirty returns modified properties
     */
    public function testGetDirtyReturnsModifiedProperties(): void
    {
        $model = new DirtyTestProduct();
        
        // Simulate loading from DB
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Original Name',
            'quantity' => 10,
            'price' => 50.00
        ]);

        // Set properties to match snapshot
        $model->id = '123';
        $model->name = 'Original Name';
        $model->quantity = 10;
        $model->price = 50.00;

        $this->assertEmpty($model->getDirty(), 'No properties should be dirty initially');

        // Modify multiple properties
        $model->name = 'New Name';
        $model->quantity = 25;

        $dirty = $model->getDirty();
        $this->assertCount(2, $dirty, 'Two properties should be dirty');
        $this->assertContains('name', $dirty, 'Name should be in dirty list');
        $this->assertContains('quantity', $dirty, 'Quantity should be in dirty list');
        $this->assertNotContains('price', $dirty, 'Price should not be in dirty list');
    }

    /**
     * Test isDirty with empty string changes
     */
    public function testIsDirtyDetectsEmptyStringChange(): void
    {
        $model = new DirtyTestProduct();
        
        // Simulate loading from DB with non-empty name
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Product Name',
            'quantity' => 10
        ]);

        // Set properties
        $model->id = '123';
        $model->name = 'Product Name';
        $model->quantity = 10;

        $this->assertFalse($model->isDirty('name'), 'Name should not be dirty initially');

        // Change to empty string
        $model->name = '';

        $this->assertTrue($model->isDirty('name'), 'Name should be dirty after setting to empty string');
        $this->assertContains('name', $model->getDirty(), 'Name should be in dirty list');
    }

    /**
     * Test that serialization of dirty empty string works correctly
     */
    public function testSerializationWithDirtyEmptyString(): void
    {
        $model = new DirtyTestProduct();
        
        // Simulate loading from DB with non-empty name
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Product Name',
            'quantity' => 10
        ]);

        // Set properties
        $model->id = '123';
        $model->name = 'Product Name';
        $model->quantity = 10;

        $array = $model->toArray();
        $this->assertEquals('Product Name', $array['name'], 'Name should be from snapshot initially');

        // Change to empty string
        $model->name = '';

        $array = $model->toArray();
        $this->assertEquals('', $array['name'], 'Empty string should override snapshot when property differs');
        $this->assertTrue($model->isDirty('name'), 'Name should be marked as dirty');
    }

    /**
     * Test isDirty with integer zero change
     */
    public function testIsDirtyDetectsZeroChange(): void
    {
        $model = new DirtyTestProduct();
        
        // Simulate loading from DB with non-zero quantity
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'quantity' => 10,
            'price' => 50.00
        ]);

        // Set properties
        $model->id = '123';
        $model->quantity = 10;
        $model->price = 50.00;

        $this->assertFalse($model->isDirty('quantity'), 'Quantity should not be dirty initially');

        // Change to zero
        $model->quantity = 0;

        $this->assertTrue($model->isDirty('quantity'), 'Quantity should be dirty after setting to zero');
        $this->assertTrue($model->isDirty(), 'Model should be dirty');
    }

    /**
     * Test that dirty tracking is cleared after syncSnapshot
     */
    public function testDirtyTrackingClearedAfterSync(): void
    {
        $model = new DirtyTestProduct();
        
        // Simulate loading from DB
        $reflection = new ReflectionClass($model);
        $rowProperty = $reflection->getProperty('__row');
        $rowProperty->setAccessible(true);
        $rowProperty->setValue($model, [
            'id' => '123',
            'name' => 'Original Name',
            'quantity' => 10
        ]);

        // Set and modify properties
        $model->id = '123';
        $model->name = 'Modified Name';
        $model->quantity = 10;

        $this->assertTrue($model->isDirty('name'), 'Name should be dirty before sync');

        // Manually call syncSnapshot (simulating what happens after save)
        $syncMethod = $reflection->getMethod('syncSnapshot');
        $syncMethod->setAccessible(true);
        $syncMethod->invoke($model);

        // After sync, snapshot should match current state, so not dirty
        $this->assertFalse($model->isDirty('name'), 'Name should not be dirty after sync');
        $this->assertFalse($model->isDirty(), 'Model should not be dirty after sync');
    }
}

