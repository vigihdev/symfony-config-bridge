<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\DependencyInjection;

use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vigihdev\Symfony\ConfigBridge\Exception\ConfigBridgeException;

final class PropertyInjector
{
    private static ?ContainerInterface $container = null;

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function inject(object $instance): void
    {
        if (self::$container === null) {
            throw new ConfigBridgeException('The container has not been set. Call Injector::setContainer() first.');
        }

        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Inject::class);

            if (count($attributes) > 0) {
                $attribute = $attributes[0];
                $serviceName = $attribute->getArguments()[0];
                self::injectService($instance, $property, $serviceName);
            }
        }
    }

    private static function injectService(object $instance, ReflectionProperty $property, string $serviceName): void
    {
        if (!self::$container->has($serviceName)) {
            throw new ConfigBridgeException(
                sprintf(
                    "Service '%s' is not available in the container. " .
                        "Please ensure the service is registered in config/services.yaml.",
                    $serviceName
                )
            );
        }

        try {
            $service = self::$container->get($serviceName);

            // Validasi type hint property
            self::validateType($property, $service, $serviceName);

            $property->setAccessible(true);
            $property->setValue($instance, $service);
        } catch (RuntimeException $e) {
            throw new ConfigBridgeException(
                "Failed to inject service '{$serviceName}': " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private static function validateType(ReflectionProperty $property, object $service, string $serviceName): void
    {
        if (!$property->hasType()) {
            return;
        }

        $propertyType = $property->getType()->getName();

        if (!($service instanceof $propertyType)) {
            throw new ConfigBridgeException(
                sprintf(
                    "Service '%s' (%s) is not compatible with property type '%s'.",
                    $serviceName,
                    get_class($service),
                    $propertyType
                )
            );
        }
    }
}
