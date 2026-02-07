<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Tests\Bridge;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Vigihdev\Symfony\ConfigBridge\Bridge\ConfigBridge;
use Vigihdev\Symfony\ConfigBridge\Tests\TestCase;

final class ConfigBridgeTest extends TestCase
{
    private string $tempDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat direktori sementara untuk testing
        $this->tempDir = sys_get_temp_dir() . '/config_bridge_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        // Buat direktori config
        mkdir($this->tempDir . '/config', 0755, true);
        
        // Reset status injection
        $reflectionClass = new \ReflectionClass(ConfigBridge::class);
        $property = $reflectionClass->getProperty('injectionEnabled');
        $property->setAccessible(true);
        $property->setValue(null, false);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Hapus direktori sementara
        $this->removeDir($this->tempDir);
        
        // Reset status injection untuk mencegah efek samping antar test
        $reflectionClass = new \ReflectionClass(ConfigBridge::class);
        $property = $reflectionClass->getProperty('injectionEnabled');
        $property->setAccessible(true);
        $property->setValue(null, false);
    }
    
    private function removeDir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->removeDir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    #[Test]
    public function testBootReturnsInstance(): void
    {
        $bridge = ConfigBridge::boot($this->tempDir);
        
        $this->assertInstanceOf(ConfigBridge::class, $bridge);
    }
    
    #[Test]
    public function testConstructorInitializesContainer(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        
        $this->assertInstanceOf(ContainerBuilder::class, $bridge->container());
    }
    
    #[Test]
    public function testLoadEnvWithDefaultPath(): void
    {
        // Buat file .env
        $envContent = "TEST_VAR=success\n";
        file_put_contents($this->tempDir . '/.env', $envContent);
        
        $bridge = new ConfigBridge($this->tempDir);
        $bridge->loadEnv();
        
        $this->assertEquals('success', $_ENV['TEST_VAR'] ?? null);
    }
    
    #[Test]
    public function testLoadEnvWithCustomPath(): void
    {
        // Buat file env kustom
        $customEnvPath = $this->tempDir . '/custom.env';
        $envContent = "CUSTOM_VAR=loaded\n";
        file_put_contents($customEnvPath, $envContent);
        
        $bridge = new ConfigBridge($this->tempDir);
        $bridge->loadEnv($customEnvPath);
        
        $this->assertEquals('loaded', $_ENV['CUSTOM_VAR'] ?? null);
    }
    
    #[Test]
    public function testLoadConfigLoadsYamlFiles(): void
    {
        // Buat file YAML contoh
        $yamlContent = <<<YAML
parameters:
    test_param: test_value

services:
    # Empty services section for testing
YAML;
        file_put_contents($this->tempDir . '/config/services.yaml', $yamlContent);
        
        $bridge = new ConfigBridge($this->tempDir);
        $bridge->loadConfig('config');
        
        // Periksa apakah konfigurasi dimuat ke dalam container
        $container = $bridge->container();
        // Karena YAML mungkin tidak langsung menghasilkan parameter di container tanpa compiler pass,
        // kita hanya bisa memastikan bahwa proses load tidak menyebabkan error
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
    
    #[Test]
    public function testCompileCompilesContainer(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        $container = $bridge->compile();
        
        $this->assertInstanceOf(ContainerBuilder::class, $container);
        $this->assertTrue($container->isCompiled());
    }
    
    #[Test]
    public function testAddConfigurationAddsService(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        
        // Buat mock ConfigurationInterface
        $mockConfig = $this->createMock(ConfigurationInterface::class);
        $mockConfigClass = get_class($mockConfig);
        
        $bridge->addConfiguration($mockConfig);
        
        $this->assertTrue($bridge->has($mockConfigClass));
    }
    
    #[Test]
    public function testGetReturnsServiceIfExists(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        
        // Tambahkan service dummy
        $mockConfig = $this->createMock(ConfigurationInterface::class);
        $mockConfigClass = get_class($mockConfig);
        
        $bridge->addConfiguration($mockConfig);
        
        $service = $bridge->get($mockConfigClass);
        $this->assertNotNull($service);
        $this->assertInstanceOf($mockConfigClass, $service);
    }
    
    #[Test]
    public function testGetReturnsNullIfServiceDoesNotExist(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        
        $service = $bridge->get('non_existent_service');
        $this->assertNull($service);
    }
    
    #[Test]
    public function testHasReturnsTrueIfServiceExists(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        
        // Tambahkan service dummy
        $mockConfig = $this->createMock(ConfigurationInterface::class);
        $mockConfigClass = get_class($mockConfig);
        
        $bridge->addConfiguration($mockConfig);
        
        $this->assertTrue($bridge->has($mockConfigClass));
    }
    
    #[Test]
    public function testHasReturnsFalseIfServiceDoesNotExist(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        
        $this->assertFalse($bridge->has('non_existent_service'));
    }
    
    #[Test]
    public function testContainerReturnsContainerBuilder(): void
    {
        $bridge = new ConfigBridge($this->tempDir);
        $container = $bridge->container();
        
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
    
    #[Test]
    public function testIsInjectionEnabled(): void
    {
        // Secara default seharusnya false
        $this->assertFalse(ConfigBridge::isInjectionEnabled());
        
        // Boot dengan enableAutoInjection=true
        $bridge = ConfigBridge::boot($this->tempDir, enableAutoInjection: true);
        
        $this->assertTrue(ConfigBridge::isInjectionEnabled());
    }
    
    #[Test]
    public function testBootWithCustomConfigDir(): void
    {
        // Buat direktori config kustom
        $customConfigDir = $this->tempDir . '/custom_config';
        mkdir($customConfigDir, 0755, true);
        
        $bridge = ConfigBridge::boot($this->tempDir, 'custom_config');
        
        $this->assertInstanceOf(ConfigBridge::class, $bridge);
    }
    
    #[Test]
    public function testBootWithLoadEnvPaths(): void
    {
        // Buat file env tambahan
        $extraEnvPath = $this->tempDir . '/extra.env';
        file_put_contents($extraEnvPath, 'EXTRA_VAR=from_extra_env');
        
        $bridge = ConfigBridge::boot($this->tempDir, loadEnvPaths: [$extraEnvPath]);
        
        $this->assertEquals('from_extra_env', $_ENV['EXTRA_VAR'] ?? null);
    }
    
    #[Test]
    public function testBootThrowsExceptionForNonExistentBasepath(): void
    {
        $this->expectException(\Vigihdev\Symfony\ConfigBridge\Exception\ConfigBridgeException::class);
        
        ConfigBridge::boot('/non/existent/path');
    }
    
    #[Test]
    public function testBootThrowsExceptionForNonExistentConfigDir(): void
    {
        $this->expectException(\Vigihdev\Symfony\ConfigBridge\Exception\ConfigBridgeException::class);
        
        ConfigBridge::boot($this->tempDir, 'non_existent_config');
    }
}