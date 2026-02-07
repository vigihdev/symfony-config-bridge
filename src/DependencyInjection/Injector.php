<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\DependencyInjection;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Injector
{
    private static ?ContainerInterface $container = null;

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function inject(object $instance): void
    {
        if (self::$container === null) {
            throw new RuntimeException('Container belum di-set. Panggil DependencyInjector::setContainer() terlebih dahulu.');
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
            throw new InvalidArgumentException(
                "Service '{$serviceName}' tidak tersedia di Container. " .
                    "Pastikan service sudah di-register di config/services.yaml."
            );
        }

        try {
            $service = self::$container->get($serviceName);

            // Validasi type hint property
            self::validateType($property, $service, $serviceName);

            $property->setAccessible(true);
            $property->setValue($instance, $service);
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                "Gagal inject service '{$serviceName}': " . $e->getMessage(),
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
            throw new RuntimeException(
                "Service '{$serviceName}' (" . get_class($service) . ") " .
                    "tidak compatible dengan property type '{$propertyType}'"
            );
        }
    }
}
