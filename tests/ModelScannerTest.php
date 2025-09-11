<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ModelScannerTest extends TestCase
{
    public function testStaticPropertyDetection()
    {
        // Create a mock class with both static and instance properties
        $testClass = new class {
            public string $instanceProperty = 'test';
            public static array $staticProperty = ['test'];
        };
        
        $reflection = new \ReflectionClass($testClass);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
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
    
    public function testStaticPropertyFiltering()
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
        
        $reflection = new \ReflectionClass($testClass);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
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
}