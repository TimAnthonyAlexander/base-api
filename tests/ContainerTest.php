<?php

use PHPUnit\Framework\TestCase;
use BaseApi\Container\Container;
use BaseApi\Container\ContainerException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBasicBinding(): void
    {
        $this->container->bind('test', 'value');
        $this->assertTrue($this->container->bound('test'));
        $this->assertEquals('value', $this->container->make('test'));
    }

    public function testSingletonBinding(): void
    {
        $this->container->singleton(TestService::class);
        
        $instance1 = $this->container->make(TestService::class);
        $instance2 = $this->container->make(TestService::class);
        
        $this->assertSame($instance1, $instance2);
    }

    public function testAutoWiring(): void
    {
        $this->container->bind(TestDependency::class);
        $this->container->bind(TestServiceWithDependency::class);
        
        $service = $this->container->make(TestServiceWithDependency::class);
        
        $this->assertInstanceOf(TestServiceWithDependency::class, $service);
        $this->assertInstanceOf(TestDependency::class, $service->getDependency());
    }

    public function testCircularDependencyDetection(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        $this->container->bind(CircularA::class);
        $this->container->bind(CircularB::class);
        
        $this->container->make(CircularA::class);
    }

    public function testInstanceBinding(): void
    {
        $instance = new TestService();
        $this->container->instance(TestService::class, $instance);
        
        $resolved = $this->container->make(TestService::class);
        
        $this->assertSame($instance, $resolved);
    }

    public function testClosureBinding(): void
    {
        $this->container->bind('closure_test', function ($container) {
            return 'closure_result';
        });
        
        $result = $this->container->make('closure_test');
        
        $this->assertEquals('closure_result', $result);
    }
}

// Test classes
class TestService
{
    public function getValue(): string
    {
        return 'test_value';
    }
}

class TestDependency
{
    public function getName(): string
    {
        return 'dependency';
    }
}

class TestServiceWithDependency
{
    private TestDependency $dependency;

    public function __construct(TestDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): TestDependency
    {
        return $this->dependency;
    }
}

class CircularA
{
    public function __construct(CircularB $b) {}
}

class CircularB
{
    public function __construct(CircularA $a) {}
}

