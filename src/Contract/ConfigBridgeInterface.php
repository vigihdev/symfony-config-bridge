<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Contract;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ConfigBridgeInterface
{

    /**
     * Load environment variables (.env).
     *
     * @param string|null $path Path to .env file or directory.
     * @return void
     */
    public function loadEnv(?string $path = null): void;

    /**
     * Load YAML or PHP configuration files into the container.
     *
     * @param string $configDir Path to configuration directory.
     * @return void
     */
    public function loadConfig(string $configDir): void;

    /**
     * Add custom configuration definitions (like Symfony Config classes).
     *
     * @param ConfigurationInterface $configuration
     * @param string|null $id
     * @return void
     */
    public function addConfiguration(ConfigurationInterface $configuration, ?string $id = null): void;

    /**
     * Build and compile the DI container.
     *
     * @return ContainerBuilder
     */
    public function compile(): ContainerBuilder;

    /**
     * Retrieve a service from the container.
     *
     * @template T
     * @param class-string<T>|string $id
     * @return T|object|null
     */
    public function get(string $id): object|null;

    /**
     * Check if a service exists in the container.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Return current compiled container instance.
     *
     * @return ContainerBuilder
     */
    public function container(): ContainerBuilder;
}
