<?php

namespace BaseApi\Tests;

use ReflectionMethod;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use BaseApi\Database\Migrations\ModelScanner;
use BaseApi\Models\BaseModel;

class ModelScannerTest extends TestCase
{
    public function testStaticPropertyDetection(): void
    {
        // Create a mock class with both static and instance properties
        $testClass = new class {
            public string $instanceProperty = 'test';

            public static array $staticProperty = ['test'];
        };
        
        $reflection = new ReflectionClass($testClass);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        
        $instanceProperties = [];
        $staticProperties = [];
        
        foreach ($properties as $property) {
            if ($property->isStatic()) {
                $staticProperties[] = $property->getName();
            } else {
                $instanceProperties[] = $property->getName();
            }
        }
        
        // Verify that we can correctly distinguish between static and instance properties
        $this->assertContains('instanceProperty', $instanceProperties);
        $this->assertNotContains('instanceProperty', $staticProperties);
        
        $this->assertContains('staticProperty', $staticProperties);
        $this->assertNotContains('staticProperty', $instanceProperties);
        
        $this->assertCount(1, $instanceProperties);
        $this->assertCount(1, $staticProperties);
    }
    
    public function testStaticPropertyFiltering(): void
    {
        // Simulate the filtering logic from ModelScanner
        $testClass = new class {
            public string $id = '';

            public string $name = '';

            public string $email = '';

            public bool $active = true;

            public static array $indexes = ['email' => 'unique'];

            public static string $table = 'test_users';
        };
        
        $reflection = new ReflectionClass($testClass);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        
        $columnProperties = [];
        
        // Apply the same filtering logic as ModelScanner
        foreach ($properties as $property) {
            $type = $property->getType();
            if (!$type) {
                continue; // Skip untyped properties
            }
            
            // Skip static properties (like $indexes, $table, etc.)
            if ($property->isStatic()) {
                continue;
            }
            
            $columnProperties[] = $property->getName();
        }
        
        // Should include instance properties
        $this->assertContains('id', $columnProperties);
        $this->assertContains('name', $columnProperties);
        $this->assertContains('email', $columnProperties);
        $this->assertContains('active', $columnProperties);
        
        // Should NOT include static properties
        $this->assertNotContains('indexes', $columnProperties);
        $this->assertNotContains('table', $columnProperties);
        
        // Should have exactly 4 column properties
        $this->assertCount(4, $columnProperties);
    }

    public function testDefaultValueExtraction(): void
    {
        // Create a mock model with various default values
        $testModel = new class extends BaseModel {
            public string $status = 'pending';

            public bool $active = true;

            public int $count = 0;

            public ?string $optional = null;

            public string $no_default;
        };

        $reflection = new ReflectionClass($testModel);
        $scanner = new ModelScanner();

        // Use reflection to call the private propertyToColumn method
        $method = new ReflectionMethod($scanner, 'propertyToColumn');
        $method->setAccessible(true);

        // Test string default value
        $statusProperty = $reflection->getProperty('status');
        $statusColumn = $method->invoke($scanner, $statusProperty);
        $this->assertEquals('pending', $statusColumn->default);
        $this->assertEquals('status', $statusColumn->name);
        $this->assertFalse($statusColumn->nullable);

        // Test boolean default value
        $activeProperty = $reflection->getProperty('active');
        $activeColumn = $method->invoke($scanner, $activeProperty);
        $this->assertEquals('1', $activeColumn->default); // Should convert to '1' for SQL
        $this->assertEquals('active', $activeColumn->name);

        // Test integer default value
        $countProperty = $reflection->getProperty('count');
        $countColumn = $method->invoke($scanner, $countProperty);
        $this->assertEquals('0', $countColumn->default);
        $this->assertEquals('count', $countColumn->name);

        // Test nullable property with null default
        $optionalProperty = $reflection->getProperty('optional');
        $optionalColumn = $method->invoke($scanner, $optionalProperty);
        $this->assertNull($optionalColumn->default);
        $this->assertEquals('optional', $optionalColumn->name);
        $this->assertTrue($optionalColumn->nullable);

        // Test property without default value
        $noDefaultProperty = $reflection->getProperty('no_default');
        $noDefaultColumn = $method->invoke($scanner, $noDefaultProperty);
        $this->assertNull($noDefaultColumn->default);
        $this->assertEquals('no_default', $noDefaultColumn->name);
        $this->assertFalse($noDefaultColumn->nullable);
    }
}
