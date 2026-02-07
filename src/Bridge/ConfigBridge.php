<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Bridge;

use Throwable;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\{ContainerBuilder, Definition};
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Vigihdev\Symfony\ConfigBridge\Contract\ConfigBridgeInterface;
use Vigihdev\Symfony\ConfigBridge\DependencyInjection\{Injector, ServiceLocator};
use Vigihdev\Symfony\ConfigBridge\Exception\ConfigBridgeException;
use Vigihdev\Symfony\ConfigBridge\Exception\Validation\ConfigBridgeValidationException;

final class ConfigBridge implements ConfigBridgeInterface
{

    private ContainerBuilder $container;

    private static bool $injectionEnabled = false;

    public static function boot(
        string $basepath,
        string $configDir = 'config',
        bool $enableAutoInjection = true,
        array $loadEnvPaths = []
    ): static {

        try {

            ConfigBridgeValidationException::validate($basepath)
                ->mustBeDirectory()
                ->mustBeReadable();

            $config = Path::join($basepath, $configDir);
            ConfigBridgeValidationException::validate($config)
                ->mustBeDirectory()
                ->mustBeReadable();

            $bridge = new self(basepath: $basepath);
            $bridge->loadEnv();

            // loadEnvPaths
            if (!empty($loadEnvPaths)) {
                foreach ($loadEnvPaths as $envPath) {
                    if (!is_file($envPath)) {
                        throw ConfigBridgeException::fileNotFound($envPath);
                    }
                    $bridge->loadEnv($envPath);
                }
            }

            $bridge->loadConfig($configDir);
            $bridge->compile();

            // Enable dependency injection setelah container ready
            if ($enableAutoInjection) {
                self::$injectionEnabled = true;
                Injector::setContainer($bridge->container);
            }

            return $bridge;
        } catch (Throwable $e) {
            throw ConfigBridgeException::handleFromThrowable($e);
        }
    }

    public function __construct(
        private readonly string $basepath
    ) {
        $this->container = new ContainerBuilder();
    }

    public function loadEnv(?string $path = null): void
    {
        $dotenv = new Dotenv();
        $envPath = $path ?? "{$this->basepath}/.env";
        if (is_file($envPath)) {
            $dotenv->usePutenv(true)->loadEnv($envPath);
        }
    }

    public function loadConfig(string $configDir): void
    {
        $locatorPath = Path::join($this->basepath, $configDir);
        $loader = new YamlFileLoader(
            container: $this->container,
            locator: new FileLocator($locatorPath)
        );

        foreach (glob("{$locatorPath}/*.yaml") as $file) {
            $loader->load(basename($file));
        }
    }

    public function compile(): ContainerBuilder
    {
        $this->container->compile(true);
        ServiceLocator::setContainer($this->container);
        return $this->container;
    }

    public function addConfiguration(ConfigurationInterface $configuration, ?string $id = null): void
    {
        $id ??= get_class($configuration);

        $definition = new Definition(get_class($configuration));
        $definition->setPublic(true);

        // Optional: tambahkan tag untuk grouping atau debugging
        $definition->addTag('vigihdev.config');
        $this->container->setDefinition($id, $definition);
    }

    public function get(string $id): ?object
    {
        return $this->container->has($id) ? $this->container->get($id) : null;
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     *
     * @return ContainerBuilder
     */
    public function container(): ContainerBuilder
    {
        return $this->container;
    }

    /**
     * Check if auto injection is enabled
     * 
     * @return bool
     */
    public static function isInjectionEnabled(): bool
    {
        return self::$injectionEnabled;
    }
}
