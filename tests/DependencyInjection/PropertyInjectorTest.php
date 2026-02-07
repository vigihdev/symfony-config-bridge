<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vigihdev\Symfony\ConfigBridge\DependencyInjection\Inject;
use Vigihdev\Symfony\ConfigBridge\DependencyInjection\PropertyInjector;
use Vigihdev\Symfony\ConfigBridge\Exception\ConfigBridgeException;
use Vigihdev\Symfony\ConfigBridge\Tests\TestCase;

final class PropertyInjectorTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject $mockContainer
     */
    private ContainerInterface $mockContainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockContainer = $this->createMock(ContainerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Reset container set in PropertyInjector
        $reflectionClass = new \ReflectionClass(PropertyInjector::class);
        $property = $reflectionClass->getProperty('container');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    #[Test]
    public function testSetContainerStoresContainer(): void
    {
        PropertyInjector::setContainer($this->mockContainer);

        // We can't directly access the private property, but we can test that
        // subsequent operations work correctly
        $testObject = new class() {
            #[Inject('test.service')]
            private $service;

            public function getService()
            {
                return $this->service;
            }
        };

        // Set up container mock expectations
        $this->mockContainer->method('has')->with('test.service')->willReturn(false);
        PropertyInjector::setContainer($this->mockContainer);

        $this->expectException(ConfigBridgeException::class);
        PropertyInjector::inject($testObject);
    }

    #[Test]
    public function testInjectThrowsExceptionIfContainerNotSet(): void
    {
        $testObject = new class() {
            #[Inject('test.service')]
            private $service;
        };

        $this->expectException(ConfigBridgeException::class);
        $this->expectExceptionMessage('The container has not been set. Call Injector::setContainer() first.');

        PropertyInjector::inject($testObject);
    }

    #[Test]
    public function testInjectSuccessfullyInjectsService(): void
    {
        $expectedService = new class() {
            public $id = 'test-service-instance';
        };

        $testObject = new class() {
            #[Inject('test.service')]
            private $service;

            public function getService()
            {
                return $this->service;
            }
        };

        // Mock container
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with('test.service')->willReturn(true);
        $containerMock->method('get')->with('test.service')->willReturn($expectedService);

        PropertyInjector::setContainer($containerMock);
        PropertyInjector::inject($testObject);

        $this->assertSame($expectedService, $testObject->getService());
    }

    #[Test]
    public function testInjectThrowsExceptionIfServiceNotInContainer(): void
    {
        $testObject = new class() {
            #[Inject('nonexistent.service')]
            private $service;
        };

        // Mock container to return false for has()
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with('nonexistent.service')->willReturn(false);

        PropertyInjector::setContainer($containerMock);

        $this->expectException(ConfigBridgeException::class);
        $this->expectExceptionMessage("Service 'nonexistent.service' is not available in the container. Please ensure the service is registered in config/services.yaml.");

        PropertyInjector::inject($testObject);
    }

    #[Test]
    public function testInjectValidatesTypeCompatibility(): void
    {
        // Create a service that doesn't match the expected type
        $wrongTypedService = new class() {};
        $correctTypedService = new class() {};

        $testObject = new class() {
            #[Inject('typed.service')]
            private \stdClass $service;

            public function getService(): \stdClass
            {
                return $this->service;
            }
        };

        // Mock container
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with('typed.service')->willReturn(true);
        $containerMock->method('get')->with('typed.service')->willReturn(new \stdClass());

        PropertyInjector::setContainer($containerMock);
        PropertyInjector::inject($testObject);

        $this->assertInstanceOf(\stdClass::class, $testObject->getService());
    }

    #[Test]
    public function testInjectHandlesMultipleProperties(): void
    {
        $service1 = new class() {
            public $name = 'service1';
        };
        $service2 = new class() {
            public $name = 'service2';
        };

        $testObject = new class() {
            #[Inject('service.one')]
            private $serviceOne;

            #[Inject('service.two')]
            private $serviceTwo;

            public function getServiceOne()
            {
                return $this->serviceOne;
            }
            public function getServiceTwo()
            {
                return $this->serviceTwo;
            }
        };

        // Mock container
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->method('has')
            ->willReturnMap([
                ['service.one', true],
                ['service.two', true],
            ]);
        $containerMock
            ->method('get')
            ->willReturnMap([
                ['service.one', $service1],
                ['service.two', $service2],
            ]);

        PropertyInjector::setContainer($containerMock);
        PropertyInjector::inject($testObject);

        $this->assertSame($service1, $testObject->getServiceOne());
        $this->assertSame($service2, $testObject->getServiceTwo());
    }
}
