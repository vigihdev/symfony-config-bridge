<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vigihdev\Symfony\ConfigBridge\DependencyInjection\ServiceLocator;
use Vigihdev\Symfony\ConfigBridge\Exception\ConfigBridgeException;
use Vigihdev\Symfony\ConfigBridge\Tests\TestCase;

final class ServiceLocatorTest extends TestCase
{
    private ContainerInterface $mockContainer;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockContainer = $this->createMock(ContainerInterface::class);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Reset container set in ServiceLocator
        $reflectionClass = new \ReflectionClass(ServiceLocator::class);
        $property = $reflectionClass->getProperty('container');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
    
    #[Test]
    public function testSetContainerStoresContainer(): void
    {
        ServiceLocator::setContainer($this->mockContainer);
        
        // We can't directly access the private property, but we can test that
        // subsequent operations work correctly
        $this->assertInstanceOf(ContainerInterface::class, ServiceLocator::getContainer());
    }
    
    #[Test]
    public function testGetContainerReturnsSetContainer(): void
    {
        ServiceLocator::setContainer($this->mockContainer);
        
        $returnedContainer = ServiceLocator::getContainer();
        
        $this->assertSame($this->mockContainer, $returnedContainer);
    }
    
    #[Test]
    public function testGetContainerThrowsExceptionIfContainerNotSet(): void
    {
        $this->expectException(ConfigBridgeException::class);
        $this->expectExceptionMessage('Container has not been set yet');
        
        ServiceLocator::getContainer();
    }
    
    #[Test]
    public function testGetDelegatesToContainer(): void
    {
        $expectedService = new class() {
            public $id = 'test-service';
        };
        
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())
                     ->method('get')
                     ->with('test.service.id')
                     ->willReturn($expectedService);
        
        ServiceLocator::setContainer($containerMock);
        $actualService = ServiceLocator::get('test.service.id');
        
        $this->assertSame($expectedService, $actualService);
    }
    
    #[Test]
    public function testGetParameterDelegatesToContainer(): void
    {
        $expectedValue = 'test-parameter-value';
        
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())
                     ->method('getParameter')
                     ->with('param.name')
                     ->willReturn($expectedValue);
        
        ServiceLocator::setContainer($containerMock);
        $actualValue = ServiceLocator::getParameter('param.name');
        
        $this->assertSame($expectedValue, $actualValue);
    }
    
    #[Test]
    public function testHasParameterDelegatesToContainer(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())
                     ->method('hasParameter')
                     ->with('param.name')
                     ->willReturn(true);
        
        ServiceLocator::setContainer($containerMock);
        $result = ServiceLocator::hasParameter('param.name');
        
        $this->assertTrue($result);
    }
    
    #[Test]
    public function testHasDelegatesToContainer(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())
                     ->method('has')
                     ->with('service.id')
                     ->willReturn(true);
        
        ServiceLocator::setContainer($containerMock);
        $result = ServiceLocator::has('service.id');
        
        $this->assertTrue($result);
    }
}