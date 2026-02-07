<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use Vigihdev\Symfony\ConfigBridge\DependencyInjection\Inject;
use Vigihdev\Symfony\ConfigBridge\Tests\TestCase;

final class InjectTest extends TestCase
{
    #[Test]
    public function testConstructSetsServiceName(): void
    {
        $serviceName = 'my.service.name';
        $inject = new Inject($serviceName);
        
        $this->assertEquals($serviceName, $inject->serviceName);
    }
    
    #[Test]
    public function testAttributeTargetIsProperty(): void
    {
        $reflection = new \ReflectionClass(Inject::class);
        $attribute = $reflection->getAttributes()[0] ?? null;
        
        // Since Inject uses #[Attribute(Attribute::TARGET_PROPERTY)], 
        // we should verify it's properly defined as attribute
        $this->assertNotNull($attribute);
        
        // More importantly, we can test that the attribute can be applied to properties
        $testClass = new class() {
            #[Inject('test.service')]
            private $testProperty;
        };
        
        $reflection = new \ReflectionClass($testClass);
        $property = $reflection->getProperty('testProperty');
        $attributes = $property->getAttributes(Inject::class);
        
        $this->assertCount(1, $attributes);
        $this->assertInstanceOf(Inject::class, $attributes[0]->newInstance());
    }
}